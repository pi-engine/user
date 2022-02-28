<?php

namespace User\Service;

use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Storage\Plugin\Serializer;

class CacheService implements ServiceInterface
{
    /**
     * @var StorageAdapterFactoryInterface
     */
    protected StorageAdapterFactoryInterface $storageFactory;

    public function __construct(
        StorageAdapterFactoryInterface $storageFactory
    ) {
        $this->storageFactory = $storageFactory;
    }

    public function setCache($key, $payload, $ttl)
    {
        // Set cache
        $cache = $this->storageFactory->create(
            'redis',
            [
                'ttl' => $ttl,
                'server' => [
                    '127.0.0.1',
                    6379
                ],
            ],
            [
                [
                    'name'    => 'serializer',
                ],
            ]
        );
        $cache->addPlugin(new Serializer());
        $cache = new SimpleCacheDecorator($cache);
        $cache->set($key, $payload);
    }

    public function getCache($key)
    {
        $cache = $this->storageFactory->create(
            'redis',
            [
                'server' => [
                    '127.0.0.1',
                    6379
                ],
            ],
            [
                [
                    'name'    => 'serializer',
                ],
            ]
        );

        $cache->addPlugin(new Serializer());
        $cache = new SimpleCacheDecorator($cache);
        return $cache->get($key);
    }
}