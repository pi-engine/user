<?php

namespace User\Service;

use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Storage\Plugin\Serializer;

class CacheService implements ServiceInterface
{
    /* @var SimpleCacheDecorator */
    protected SimpleCacheDecorator $cache;

    protected string $userKeyPattern = 'user-keys-%s';

    public function __construct(
        StorageAdapterFactoryInterface $storageFactory
    ) {
        // Set cache
        $cache = $storageFactory->create(
            'redis',
            [
                'server' => [
                    '127.0.0.1',
                    6379,
                ],
            ],
            [
                [
                    'name' => 'serializer',
                ],
            ]
        );
        $cache->addPlugin(new Serializer());
        $this->cache = new SimpleCacheDecorator($cache);
    }

    public function setCache($key, $payload, $ttl)
    {
        $this->cache->set($key, $payload);
    }

    public function getCache($key)
    {
        return $this->cache->get($key);
    }

    public function removeKey($key): void
    {
        $this->cache->delete($key);
    }

    public function removeKeys($userId): void
    {
        $key = sprintf($this->userKeyPattern, $userId);
        $items = $this->cache->get($key);
        foreach ($items as $item) {
            $this->cache->delete($item);
        }
    }

    public function manageUserKey($userId, $item): void
    {
        $key = sprintf($this->userKeyPattern, $userId);
        if ($this->cache->has($key)) {
            $items = $this->cache->get($key);
            $this->cache->set($key, array_unique(array_merge($items, [$item])));
        } else {
            $this->cache->set($key, [$item]);
        }
    }
}