<?php

namespace Pi\User\Service;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Laminas\Math\Rand;
use Pi\Core\Service\CacheService;

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

    public function encryptToken($params): array
    {
        // Set key
        $uniqId = $this->setUniqId($params);

        // Set payload
        $payload = [
            'id'   => $uniqId,
            'uid'  => $params['account']['id'],
            'iat'  => time(),
            'exp'  => $params['exp'] ?? time() + $this->setTtl($params),
            'type' => $params['type'],
            'iss'  => $this->config['iss'],
            'aud'  => $this->config['aud'],
            'sub'  => sprintf('user-%s', $params['account']['id']),
        ];

        // Set additional information in the payload
        if (isset($this->config['additional']) && !empty($this->config['additional'])) {
            foreach ($this->config['additional'] as $additional) {
                $payload[$additional] = '';
                if (isset($params['account'][$additional])) {
                    $payload[$additional] = $params['account'][$additional];
                }
            }
        }

        return [
            'token'   => JWT::encode($payload, $this->setEncryptKey(), $this->setAlgorithm()),
            'key'     => $uniqId,
            'payload' => $payload,
        ];
    }

    public function decryptToken($token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->setDecryptKey(), $this->setAlgorithm()));

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

    private function setUniqId($params): string
    {
        switch ($params['type']) {
            default:
            case 'access':
                return hash(
                    'sha256',
                    sprintf(
                        'a-%s-%s',
                        $params['account']['id'],
                        Rand::getString('16', 'abcdefghijklmnopqrstuvwxyz0123456789')
                    )
                );

            case 'refresh':
                return hash(
                    'sha256',
                    sprintf(
                        'r-%s-%s',
                        $params['account']['id'],
                        Rand::getString('16', 'abcdefghijklmnopqrstuvwxyz0123456789')
                    )
                );
        }
    }

    private function setTtl($params): int
    {
        switch ($params['type']) {
            default:
            case 'access':
                return (int)$this->config['exp_access'];

            case 'refresh':
                return (int)$this->config['exp_refresh'];
        }
    }

    private function setAlgorithm(): string
    {
        if (isset($this->config['type']) && $this->config['type'] == 'keys') {
            return 'RS256';
        }

        return 'HS256';
    }

    private function setEncryptKey()
    {
        if (
            isset($this->config['type'])
            && $this->config['type'] == 'keys'
            && isset($this->config['private_key'])
            && !empty($this->config['private_key'])
        ) {
            return file_get_contents($this->config['private_key']);
        }

        return $this->config['secret'];
    }

    private function setDecryptKey()
    {
        if (
            isset($this->config['type'])
            && $this->config['type'] == 'keys'
            && isset($this->config['public_key'])
            && !empty($this->config['public_key'])
        ) {
            return file_get_contents($this->config['public_key']);
        }

        return $this->config['secret'];
    }
}