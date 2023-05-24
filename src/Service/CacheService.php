<?php

namespace User\Service;

use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Storage\Plugin\Serializer;
use Psr\SimpleCache\InvalidArgumentException;

class CacheService implements ServiceInterface
{
    public array $userValuePattern
        = [
            'account'       => [],
            'roles'         => [],
            'access_keys'   => [],
            'refresh_keys'  => [],
            'otp'           => [],
            'device_tokens' => [],
        ];

    /* @var SimpleCacheDecorator */
    protected SimpleCacheDecorator $cache;

    protected string $userKeyPattern = 'user-%s';

    protected string $roleKeyPattern = 'roles-list';

    /* @var array */
    protected array $config;

    public function __construct(StorageAdapterFactoryInterface $storageFactory, $config)
    {
        // Set cache
        $cache = $storageFactory->create($config['storage'], $config['options'], $config['plugins']);
        $cache->addPlugin(new Serializer());
        $this->cache = new SimpleCacheDecorator($cache);
    }

    public function setItem(string $key, array $value = []): array
    {
        $this->cache->set($key, $value);

        return $value;
    }

    public function getItem($key): array
    {
        $list = [];
        if ($this->cache->has($key)) {
            $list = $this->cache->get($key);
        }

        return $list;
    }

    public function addItem(int $userId, string $key, string $value): void
    {
        $user = $this->getUser($userId);
        if (!empty($user) && !empty($value)) {
            switch ($key) {
                case 'access_keys':
                    $user['access_keys'] = array_unique(array_merge($user['access_keys'], [$value]));
                    $this->setUser($userId, ['access_keys' => $user['access_keys']]);
                    break;

                case 'refresh_keys':
                    $user['refresh_keys'] = array_unique(array_merge($user['refresh_keys'], [$value]));
                    $this->setUser($userId, ['refresh_keys' => $user['refresh_keys']]);
                    break;

                case 'roles':
                    $user['roles'] = array_unique(array_merge($user['roles'], [$value]));
                    $this->setUser($userId, ['roles' => $user['roles']]);
                    break;
                //TODO:review this solution
                case 'device_tokens':
                    $user['device_tokens'] =$value;// array_unique(array_merge($user['device_tokens'], [$value]));
                    $this->setUser($userId, ['device_tokens' => $user['device_tokens']]);
                    break;
            }
        }
    }

    public function removeItem(int $userId, string $key, string $value): void
    {
        $user = $this->getUser($userId);
        if (!empty($user) && !empty($value)) {
            switch ($key) {
                case 'access_keys':
                    $user['access_keys'] = array_combine($user['access_keys'], $user['access_keys']);
                    if (isset($user['access_keys'][$value])) {
                        unset($user['access_keys'][$value]);
                    }
                    $this->setUser($userId, ['access_keys' => array_values($user['access_keys'])]);
                    break;

                case 'refresh_keys':
                    $user['refresh_keys'] = array_combine($user['refresh_keys'], $user['refresh_keys']);
                    if (isset($user['refresh_keys'][$value])) {
                        unset($user['refresh_keys'][$value]);
                    }
                    $this->setUser($userId, ['refresh_keys' => array_values($user['refresh_keys'])]);
                    break;

                case 'roles':
                    $user['roles'] = array_combine($user['roles'], $user['roles']);
                    if (isset($user['roles'][$value])) {
                        unset($user['roles'][$value]);
                    }
                    $this->setUser($userId, ['roles' => array_values($user['roles'])]);
                    break;

                //TODO:review this solution
                case 'device_tokens':
                    $user['device_tokens'] =$value;// array_unique(array_merge($user['device_tokens'], [$value]));
                    $this->setUser($userId, ['device_tokens' => $user['device_tokens']]);
                    break;
//                case 'device_tokens':
//                    $user['device_tokens'] = array_combine($user['device_tokens'], $user['device_tokens']);
//                    if (isset($user['device_tokens'][$value])) {
//                        unset($user['device_tokens'][$value]);
//                    }
//                    $this->setUser($userId, ['device_tokens' => array_values($user['device_tokens'])]);
//                    break;
            }
        }
    }

    public function getUser(int $userId): array
    {
        $key  = sprintf($this->userKeyPattern, $userId);
        $user = [];
        if ($this->cache->has($key)) {
            $user = $this->cache->get($key);

            // Set ID as int
            $user['account']['id'] = (int)$user['account']['id'];
        }

        return $user;
    }

    /**
     * User information set/update in cache, account and role info for to update after each call but access keys merged
     *
     * @param int   $userId
     * @param array $params
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function setUser(int $userId, array $params): array
    {
        $key = sprintf($this->userKeyPattern, $userId);

        // Get and check user
        $user = $this->getUser($userId);
        if (empty($user)) {
            $user = $this->userValuePattern;
        }

        // Set params
        if (isset($params['account']) && !empty($params['account'])) {
            // Set ID as int
            $params['account']['id'] = (int)$params['account']['id'];

            $user['account'] = $params['account'];
        }
        if (isset($params['access_keys']) && !empty($params['access_keys'])) {
            $user['access_keys'] = $params['access_keys'];
        }
        if (isset($params['refresh_keys']) && !empty($params['refresh_keys'])) {
            $user['refresh_keys'] = $params['refresh_keys'];
        }
        if (isset($params['roles']) && !empty($params['roles'])) {
            $user['roles'] = $params['roles'];
        }
        if (isset($params['otp']) && !empty($params['otp'])) {
            $user['otp'] = $params['otp'];
        }
        if (isset($params['device_tokens']) && !empty($params['device_tokens'])) {
            $user['device_tokens'] = $params['device_tokens'];
        }

        // Set/Reset cache
        $this->cache->set($key, $user);

        return $user;
    }

    public function deleteUser($userId): void
    {
        $key = sprintf($this->userKeyPattern, $userId);
        $this->cache->delete($key);
    }
}