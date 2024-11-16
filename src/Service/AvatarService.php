<?php

namespace Pi\User\Service;

use ArrayObject;
use Exception;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Traversable;

class AvatarService implements ServiceInterface
{
    /** @var HistoryService */
    protected HistoryService $historyService;

    /* @var array */
    protected array $config;

    public function __construct(
        HistoryService $historyService,
                       $config
    ) {
        $this->historyService = $historyService;
        $this->config         = $config;
    }

    public function uploadAvatar($uploadFile, $account): array
    {
        $userPath = $this->getUserPath($account);

        // Prepare an array to hold the URLs of the saved images
        $uploadPath = sprintf('%s/%s', $this->config['public_path'], $userPath);
        $this->mkdir($uploadPath);

        // Define the standard avatar sizes
        $sizes = [
            '16'  => new Box(16, 16),
            '32'  => new Box(32, 32),
            '48'  => new Box(48, 48),
            '64'  => new Box(64, 64),
            '96'  => new Box(96, 96),
            '128' => new Box(128, 128),
            '256' => new Box(256, 256),
            '512' => new Box(512, 512),
        ];

        // Create an Imagine instance
        $imagine = new Imagine();

        // Open the uploaded file with Imagine
        $image = $imagine->open($uploadFile->getStream()->getMetadata('uri'));

        // Process each size
        $imageUris = [];
        foreach ($sizes as $sizeName => $sizeBox) {
            // Resize the image
            $resizedImage = $image->copy()->resize($sizeBox);

            // Define the filename and target path for each size
            $avatarName = "{$sizeName}.png";
            $avatarPath = rtrim($uploadPath, '/') . '/' . $avatarName;

            // Save the resized image as a PNG file
            $resizedImage->save($avatarPath);

            // Generate the URL for the saved image
            $imageUris[$sizeName] = sprintf('%s/%s', $userPath, $avatarName);
        }

        // Save log
        $this->historyService->logger('addAvatar', ['request' => [], 'account' => $account]);

        return [
            'avatar'        => $imageUris['48'],
            'avatar_params' => [
                'type' => 'local',
                'path' => $userPath,
                'uri'  => $imageUris,
            ],
        ];
    }

    public function createUri($profile): array
    {
        // Set avatar
        if (
            isset($profile['information']['avatar_params']['uri'])
            && !empty($profile['information']['avatar_params']['uri'])
            && $profile['information']['avatar_params']['type'] == 'local'
        ) {
            $profile['avatar'] = sprintf('%s/%s', $this->config['avatar_uri'], $profile['avatar']);
            foreach ($profile['information']['avatar_params']['uri'] as $key => $value) {
                $profile['information']['avatar_params']['uri'][$key] = sprintf('%s/%s', $this->config['avatar_uri'], $value);
            }

        }

        return $profile;
    }

    protected function mkdir($dirs, $mode = 0777): static
    {
        foreach ($this->toIterator($dirs) as $dir) {
            if (is_dir($dir)) {
                continue;
            }

            if (true !== @mkdir($dir, $mode, true)) {
                throw new Exception(sprintf('Failed to create %s', $dir));
            }
        }

        return $this;
    }

    protected function toIterator($files)
    {
        if (!$files instanceof Traversable) {
            $files = new ArrayObject(
                is_array($files)
                    ? $files : [$files]
            );
        }

        return $files;
    }

    protected function getUserPath($account): string
    {
        return hash('sha256', sprintf('%s-%s', $account['id'], $account['time_created']));
    }
}