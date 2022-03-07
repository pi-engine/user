<?php

namespace User\Service;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Laminas\Config\Config;
use Laminas\Math\Rand;

class TokenService implements ServiceInterface
{
    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var CacheService
     */
    protected CacheService $cacheService;

    public function __construct(
        CacheService $cacheService
    ) {
        $this->config       = new Config(include __DIR__ . '/../../config/custom.config.php');
        $this->cacheService = $cacheService;
    }

    public function generate($params): string
    {
        // Set cache key
        $key = sprintf('user%s-%s', $params['user_id'], Rand::getString('16', 'abcdefghijklmnopqrstuvwxyz0123456789'));

        // Set payload
        switch ($params['type']) {
            default:
            case 'access':
                $ttl     = $this->config->jwt->exp_access;
                $payload = [
                    'id'    => $key,
                    'uid'   => $params['user_id'],
                    'iat'   => time(),
                    'exp'   => time() + $ttl,
                    'type'  => $params['type'],
                    'roles' => $params['roles'],
                ];
                break;

            case 'refresh':
                $ttl     = $this->config->jwt->exp_refresh;
                $payload = [
                    'id'   => $key,
                    'uid'  => $params['user_id'],
                    'iat'  => time(),
                    'exp'  => time() + $ttl,
                    'type' => $params['type'],
                ];
                break;
        }

        // Set to cache
        $this->cacheService->setCache($key, $payload, $ttl);

        return JWT::encode($payload, $this->config->jwt->secret, 'HS256');
    }

    public function parse($token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config->jwt->secret, 'HS256'));

            // Get data from cache
            $cacheCheck = $this->cacheService->getCache($decoded->id);

            if (isset($cacheCheck['id']) && $cacheCheck['exp'] > time()) {
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