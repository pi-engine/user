<?php

declare(strict_types=1);

namespace Pi\User\Service;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Laminas\Validator\Uri;
use Pi\Media\Service\S3Service;
use Pi\Media\Storage\LocalStorage;

class AvatarService implements ServiceInterface
{
    /** @var AccountService */
    protected AccountService $accountService;

    /** @var HistoryService */
    protected HistoryService $historyService;

    /** @var LocalStorage */
    protected LocalStorage $localStorage;

    /** @var S3Service */
    protected S3Service $s3Service;

    /* @var array */
    protected array $config;

    /* @var Uri */
    protected Uri $validator;

    /* @var array */
    protected array $imageUris = [];

    /* @var string */
    protected string $userPath = '';

    /* @var string */
    protected string $extension = 'png';

    public function __construct(
        AccountService $accountService,
        HistoryService $historyService,
        LocalStorage   $localStorage,
        S3Service      $s3Service,
                       $config
    ) {
        $this->accountService = $accountService;
        $this->historyService = $historyService;
        $this->localStorage   = $localStorage;
        $this->s3Service      = $s3Service;
        $this->config         = $config;
        $this->validator      = new Uri(['allowRelative' => false]);
    }

    public function uploadAvatar($uploadFile, $account): array
    {
        // Get user profile
        $account = array_merge($account, $this->accountService->getProfile(['user_id' => (int)$account['id']]));

        // Set user path
        $this->setUserPath($account);
        $this->setExtension($uploadFile->getClientFilename());

        // Upload and save avatar on selected storage
        switch ($this->config['storage']) {
            case 's3':
                return $this->s3Avatar($uploadFile, $account);
                break;

            default:
            case 'local':
                return $this->localAvatar($uploadFile, $account);
                break;
        }
    }

    protected function localAvatar($uploadFile, $account): array
    {
        // Define the standard avatar sizes
        $sizesBox = [
            '128' => new Box(128, 128),
            '256' => new Box(256, 256),
            '512' => new Box(512, 512),
        ];

        // Create an Imagine instance
        $imagine = new Imagine();

        // Open the uploaded file with Imagine
        $image = $imagine->open($uploadFile->getStream()->getMetadata('uri'));

        // Delete old path and files if exist
        if (isset($account['information']['avatar_params']['path']) && !empty($account['information']['avatar_params']['path'])) {
            $oldPath = sprintf('%s/%s', $this->config['public_path'], $account['information']['avatar_params']['path']);
            if ($this->localStorage->exists($oldPath)) {
                $this->localStorage->remove($oldPath);
            }
        }

        // Prepare an array to hold the URLs of the saved images
        $uploadPath = sprintf('%s/%s', $this->config['public_path'], $this->userPath);
        $this->localStorage->mkdir($uploadPath);

        // Process each size
        foreach ($sizesBox as $sizeName => $sizeBox) {
            // Define the filename and target path for each size
            $avatarName = sprintf('%s.%s', $sizeName, $this->extension);
            $avatarPath = rtrim($uploadPath, '/') . '/' . $avatarName;

            // Resize and save the image
            $resizedImage = $image->copy()->resize($sizeBox);
            $resizedImage->save($avatarPath);

            // Generate the URL for the saved image
            $url = sprintf('%s/%s/%s', $this->config['avatar_uri'], $this->userPath, $avatarName);
            if (!empty($url) && $this->validator->isValid($url)) {
                $this->imageUris[$sizeName] = $url;
            }
        }

        return $this->saveAvatar($account);
    }

    protected function s3Avatar($uploadFile, $account): array
    {
        // Define the standard avatar sizes
        $sizesBox = [
            '128' => new Box(128, 128),
            '256' => new Box(256, 256),
            '512' => new Box(512, 512),
        ];

        // Create an Imagine instance
        $imagine = new Imagine();

        // Open the uploaded file with Imagine
        $image = $imagine->open($uploadFile->getStream()->getMetadata('uri'));

        // Delete old path and files if exist
        if (isset($account['information']['avatar_params']['path']) && !empty($account['information']['avatar_params']['path'])) {
            $bucket = $this->s3Service->deleteBucket($account['information']['avatar_params']['path']);
            if (!$bucket['result']) {
                return $bucket;
            }
        }

        // Check if the bucket exists and create if not exist
        $bucket = $this->s3Service->setOrGetBucket($this->userPath, [
            'Version'   => '2012-10-17',
            'Statement' => [
                [
                    'Sid'       => 'PublicReadGetObject',
                    'Effect'    => 'Allow',
                    'Principal' => '*',
                    'Action'    => ['s3:GetObject'],
                    'Resource'  => ["arn:aws:s3:::{$this->userPath}/*"],
                ],
            ],
        ]);

        // Check result
        if (!$bucket['result']) {
            return $bucket;
        }

        foreach ($sizesBox as $sizeName => $sizeBox) {
            // Define the filename and target path for each size
            $avatarName = sprintf('%s.%s', $sizeName, $this->extension);
            $avatarPath = sys_get_temp_dir() . '/' . $avatarName;

            // Resize and save the image
            $resizedImage = $image->copy()->resize($sizeBox);
            $resizedImage->save($avatarPath);

            // Upload the file stream to s3
            $response = $this->s3Service->putFile([
                'Bucket'     => $this->userPath,
                'Key'        => $avatarName,
                'SourceFile' => $avatarPath,
                'ACL'        => 'public-read',
                'Metadata'   => [
                    'user_id' => $account['id'],
                ],
            ]);

            // Check result
            if (!$response['result']) {
                return $response;
            }

            // Remove the temporary file after upload
            unlink($avatarPath);

            // Generate the URI for S3 storage
            $url = (string)$response['data']['@metadata']['effectiveUri'] ?? '';
            if (!empty($url) && $this->validator->isValid($url)) {
                $this->imageUris[$sizeName] = $url;
            }
        }

        return $this->saveAvatar($account);
    }

    protected function saveAvatar($account): array
    {
        // Set avatar
        $avatar = [
            'avatar'        => $this->imageUris['256'] ?? '',
            'avatar_params' => [
                'type' => $this->config['storage'],
                'path' => $this->userPath,
                'uri'  => $this->imageUris,
            ],
        ];

        // Update account
        $account = $this->accountService->updateAccount($avatar, $account);

        // Save log
        $this->historyService->logger('addAvatar', ['request' => [], 'account' => $account]);

        return [
            'result' => true,
            'data'   => $account,
            'error'  => [],
        ];
    }

    protected function setUserPath($account): void
    {
        if (isset($this->config['storage']) && $this->config['storage'] = 's3') {
            $this->userPath = sprintf('avatar-%s-%s-%s', $this->config['platform'], $account['id'], rand(1000, 9999));
        } else {
            $this->userPath = sprintf('%s-%s', $account['id'], rand(1000, 9999));
        }
    }

    protected function setExtension($fileName): void
    {
        $fileInfo        = pathinfo($fileName);
        $this->extension = strtolower($fileInfo['extension']);
    }
}