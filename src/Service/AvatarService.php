<?php

declare(strict_types=1);

namespace Pi\User\Service;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
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

    protected S3Service $s3Service;

    /* @var array */
    protected array $config;

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
    }

    public function uploadAvatar($uploadFile, $account): array
    {
        // Get user profile
        $profile = $this->accountService->getProfile(['user_id' => (int)$account['id']]);

        // Set user path
        $userPath = $this->getUserPath($account);

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

        // Check and set storage
        $imageUris = [];
        if (isset($this->config['storage']) && $this->config['storage'] === 's3') {
            // Delete old path and files if exist
            $bucket = $this->s3Service->deleteBucket($profile['information']['avatar_params']['path']);
            if (!$bucket['result']) {
                return $bucket;
            }

            // Set bucket name
            $userPath = sprintf('avatar-%s-%s', $this->config['platform'], $userPath);

            // Check if the bucket exists and create if not exist
            $bucket = $this->s3Service->setOrGetBucket($userPath, [
                'Version'   => '2012-10-17',
                'Statement' => [
                    [
                        'Sid'       => 'PublicReadGetObject',
                        'Effect'    => 'Allow',
                        'Principal' => '*',
                        'Action'    => ['s3:GetObject'],
                        'Resource'  => ["arn:aws:s3:::{$userPath}/*"],
                    ],
                ],
            ]);

            // Check result
            if (!$bucket['result']) {
                return $bucket;
            }

            foreach ($sizesBox as $sizeName => $sizeBox) {
                // Define the filename and target path for each size
                $avatarName = "{$sizeName}.png";
                $avatarPath = sys_get_temp_dir() . '/' . $avatarName;

                // Resize and save the image
                $resizedImage = $image->copy()->resize($sizeBox);
                $resizedImage->save($avatarPath);

                // Upload the file stream to s3
                $response = $this->s3Service->putFile([
                    'Bucket'     => $userPath,
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
                $imageUris[$sizeName] = $response['data']['@metadata']['effectiveUri'];
            }
        } else {
            // Delete old path and files if exist
            $oldPath = sprintf('%s/%s', $this->config['public_path'], $profile['information']['avatar_params']['path']);
            if ($this->localStorage->exists($oldPath)) {
                $this->localStorage->remove($oldPath);
            }

            // Prepare an array to hold the URLs of the saved images
            $uploadPath = sprintf('%s/%s', $this->config['public_path'], $userPath);
            $this->localStorage->mkdir($uploadPath);

            // Process each size
            foreach ($sizesBox as $sizeName => $sizeBox) {
                // Define the filename and target path for each size
                $avatarName = "{$sizeName}.png";
                $avatarPath = rtrim($uploadPath, '/') . '/' . $avatarName;

                // Resize and save the image
                $resizedImage = $image->copy()->resize($sizeBox);
                $resizedImage->save($avatarPath);

                // Generate the URL for the saved image
                $imageUris[$sizeName] = sprintf('%s/%s/%s', $this->config['avatar_uri'], $userPath, $avatarName);
            }
        }

        // Set avatar
        $avatar = [
            'avatar'        => $imageUris['256'] ?? '',
            'avatar_params' => [
                'type' => $this->config['storage'],
                'path' => $userPath,
                'uri'  => $imageUris,
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

    protected function getUserPath($account): string
    {
        return sprintf('%s-%s', $account['id'], rand(1000, 9999));
    }
}