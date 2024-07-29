<?php

namespace User\Service;

use DateTime;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Storage\Plugin\Serializer;
use Redis;

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
            'multi_factor'  => [],
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
        $this->cache  = new SimpleCacheDecorator($cache);
        $this->config = $config;
    }

    public function getItem($key): array
    {
        $item = [];
        if ($this->cache->has($key)) {
            $item = $this->cache->get($key);
        }

        return $item;
    }

    public function setItem(string $key, array $value = [], $ttl = null): array
    {
        $this->cache->set($key, $value, $ttl);

        return $value;
    }

    public function deleteItem(string $key): void
    {
        $this->cache->delete($key);
    }

    public function deleteItems(array $array): void
    {
        foreach ($array as $key) {
            $this->cache->delete($key);
        }
    }

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
        if (isset($params['multi_factor']) && !empty($params['multi_factor'])) {
            $user['multi_factor'] = $params['multi_factor'];
        }
        if (isset($params['permission']) && !empty($params['permission'])) {
            $user['permission'] = $params['permission'];
        }
        if (isset($params['authorization']) && !empty($params['authorization'])) {
            $user['authorization'] = $params['authorization'];
        }

        // Set/Reset cache
        $this->setItem($key, $user);

        return $user;
    }

    public function getUser(int $userId): array
    {
        $key  = sprintf($this->userKeyPattern, $userId);
        $user = $this->getItem($key);
        if (!empty($user)) {
            $user['account']['id'] = (int)$user['account']['id'];
        }

        return $user;
    }

    public function deleteUser($userId): void
    {
        $key = sprintf($this->userKeyPattern, $userId);
        $this->deleteItem($key);
    }

    public function setUserItem(int $userId, string $key, string $value): void
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

                case 'multi_factor':
                    $user['multi_factor'] = array_unique(array_merge($user['multi_factor'], [$value]));
                    $this->setUser($userId, ['multi_factor' => $user['multi_factor']]);
                    break;

                // TODO: review this solution
                case 'device_tokens':
                    $user['device_tokens'] = $value;// array_unique(array_merge($user['device_tokens'], [$value]));
                    $this->setUser($userId, ['device_tokens' => $user['device_tokens']]);
                    break;
            }
        }
    }

    public function deleteUserItem(int $userId, string $key, string $value): void
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

                case 'multi_factor':
                    $user['multi_factor'] = array_combine($user['multi_factor'], $user['multi_factor']);
                    if (isset($user['multi_factor'][$value])) {
                        unset($user['multi_factor'][$value]);
                    }
                    $this->setUser($userId, ['multi_factor' => array_values($user['multi_factor'])]);
                    break;

                // TODO: review this solution
                case 'device_tokens':
                    $user['device_tokens'] = $value;// array_unique(array_merge($user['device_tokens'], [$value]));
                    $this->setUser($userId, ['device_tokens' => $user['device_tokens']]);
                    break;
            }
        }
    }

    public function updateUserRoles(int $userId, array $roles, string $section = 'api'): array
    {
        // Get and check user
        $key  = sprintf($this->userKeyPattern, $userId);
        $user = $this->getUser($userId);

        if (!empty($user)) {
            // Update roles
            switch ($section) {
                case 'api':
                    $user['roles'] = array_unique(array_merge($user['roles'], $roles));
                    break;

                case 'admin':
                    // Todo
                    break;
            }

            // Set/Reset cache
            $this->setItem($key, $user);
        }

        return $user;
    }

    public function getCacheList()
    {
        // Setup redis
        $redis = new Redis();
        $redis->connect($this->config['options']['server']['host'], $this->config['options']['server']['port']);

        // Get keys
        $keys = $redis->keys(sprintf('%s:*', $this->config['options']['namespace']));

        // Set list
        $list = [];
        foreach ($keys as $key) {
            // Set new key name
            $key = str_replace(sprintf('%s:', $this->config['options']['namespace']), '', $key);

            // Get ttl
            $ttl = $redis->ttl($key);

            // Set date
            $currentTime    = new DateTime();
            $expirationTime = new DateTime();
            $expirationTime->setTimestamp(time() + $ttl);

            // Set date
            $expirationDate = $expirationTime->format('Y-m-d H:i:s');
            $interval       = $currentTime->diff($expirationTime);

            // Add to list
            $list[] = [
                'key'        => $key,
                'ttl'        => $ttl,
                'expiration' => [
                    'date'     => $expirationDate,
                    'interval' => [
                        'days'    => $interval->days,
                        'hours'   => $interval->h,
                        'minutes' => $interval->i,
                    ],
                ],
            ];
        }

        echo '<pre>';
        print_r($list);
        die;
    }
}