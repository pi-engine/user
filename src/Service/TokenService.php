<?php

namespace User\Service;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Laminas\Config\Config;
use Laminas\Math\Rand;

class TokenService implements ServiceInterface
{
    /* @var CacheService */
    protected CacheService $cacheService;

    /* @var array */
    protected array $config;

    public function __construct(
        CacheService $cacheService,
        $config
    ) {
        $this->cacheService = $cacheService;
        $this->config       = $config;
    }

    public function generate($params): array
    {
        // Get key
        $key = $this->key($params);

        // Get ttl
        $ttl = $this->ttl($params);

        // Set payload
        $payload = [
            'id'   => $key,
            'uid'  => $params['user_id'],
            'iat'  => time(),
            'exp'  => $params['exp'] ?? time() + $ttl,
            'type' => $params['type'],
        ];

        return [
            'token'   => JWT::encode($payload, $this->config['secret'], 'HS256'),
            'key'     => $key,
            'payload' => $payload,
        ];
    }

    public function key($params): string
    {
        switch ($params['type']) {
            default:
            case 'access':
                return sprintf(
                    'user-access-%s-%s',
                    $params['user_id'],
                    Rand::getString('16', 'abcdefghijklmnopqrstuvwxyz0123456789')
                );

            case 'refresh':
                return sprintf(
                    'user-refresh-%s-%s',
                    $params['user_id'],
                    Rand::getString('16', 'abcdefghijklmnopqrstuvwxyz0123456789')
                );
        }
    }

    public function ttl($params)
    {
        switch ($params['type']) {
            default:
            case 'access':
                return $this->config['exp_access'];

            case 'refresh':
                return $this->config['exp_refresh'];
        }
    }

    public function parse($token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config['secret'], 'HS256'));

            // Get data from cache
            $cacheUser = $this->cacheService->getUser((int)$decoded->uid);

            if (
                !empty($decoded)
                && !empty($cacheUser)
                && $decoded->type == 'access'
                && in_array($decoded->id, $cacheUser['access_keys'])
            ) {
                return [
                    'status'  => true,
                    'id'      => $decoded->id,
                    'user_id' => $decoded->uid,
                    'type'    => $decoded->type,
                ];
            } elseif (
                !empty($decoded)
                && !empty($cacheUser)
                && $decoded->type == 'refresh'
                && in_array($decoded->id, $cacheUser['refresh_keys'])
            ) {
                return [
                    'status'  => true,
                    'id'      => $decoded->id,
                    'user_id' => $decoded->uid,
                    'type'    => $decoded->type,
                ];
            } else {
                return [
                    'status'  => false,
                    'message' => 'Token not valid !',
                ];
            }
        } catch (Exception $e) {
            return [
                'status'  => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}