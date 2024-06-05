<?php

namespace User\Service;

use ArrayObject;
use Exception;
use Laminas\Filter\FilterChain;
use Laminas\Filter\PregReplace;
use Laminas\Filter\StringToLower;
use Laminas\Filter\Word\SeparatorToDash;
use Laminas\Math\Rand;
use Logger\Service\LoggerService;
use Traversable;

class AvatarService implements ServiceInterface
{
    /* @var LoggerService */
    protected LoggerService $loggerService;

    /* @var array */
    protected array $config;

    public function __construct(
        LoggerService $loggerService,
        $config
    ) {
        $this->loggerService = $loggerService;
        $this->config        = $config;
    }

    public function uploadAvatar($uploadFile, $account): array
    {
        $fileInfo   = pathinfo($uploadFile->getClientFilename());
        $userPath   = hash('sha256', $account['id']);
        $uploadPath = sprintf('%s/%s', $this->config['public_path'], $userPath);
        $avatarName = $this->makeFileName($fileInfo['filename']);
        $avatarName = strtolower(sprintf('%s-%s.%s', $avatarName, Rand::getString('16', 'abcdefghijklmnopqrstuvwxyz0123456789'), $fileInfo['extension']));
        $avatarPath = sprintf('%s/%s', $uploadPath, $avatarName);
        $avatarUri  = sprintf('%s/%s/%s', $this->config['avatar_uri'], $userPath, $avatarName);

        // Make a path storage
        $this->mkdir($uploadPath);

        // Save file to storage
        $uploadFile->moveTo($avatarPath);

        $avatar = [
            'type' => 'upload',
            'name' => $avatarName,
            'path' => $userPath,
            'uri'  => $avatarUri,
        ];

        return $this->canonizeAvatar($avatar);
    }

    public function canonizeAvatar($avatar): array
    {
        return [
            'avatar'        => $avatar['uri'],
            'avatar_params' => [
                'type' => $avatar['type'],
                'name' => $avatar['name'],
                'path' => $avatar['path'],
            ],
        ];
    }

    public function makeFileName($fileName)
    {
        $filterChain = new FilterChain();
        $filterChain->attach(new StringToLower())
            ->attach(new SeparatorToDash())
            ->attach(new PregReplace('/[^a-zA-Z0-9-]/', '-'));

        return $filterChain->filter($fileName);
    }

    public function mkdir($dirs, $mode = 0777)
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
}