<?php

declare(strict_types=1);

namespace Pi\User\Service;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Pi\Core\Service\CacheService;
use Random\RandomException;

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

    /**
     * @throws RandomException
     */
    public function encryptToken($params): array
    {
        // Generate a unique ID
        $tokenId = $params['id'] ?? $this->setTokenKey($params);

        // Build the payload
        $payload = [
            'id'   => $tokenId,
            'uid'  => $params['account']['id'],
            'iat'  => time(),
            'exp'  => $params['exp'] ?? time() + $this->setTtl($params),
            'type' => $params['type'],
            'iss'  => $this->config['iss'] ?? '',
            'aud'  => $this->config['aud'] ?? '',
            'sub'  => sprintf('user-%s', $params['account']['id']),
        ];

        // Add additional payload fields if configured
        if (isset($this->config['additional']) && !empty($this->config['additional'])) {
            foreach ($this->config['additional'] as $key) {
                $payload[$key] = $params['account'][$key] ?? '';
            }
        }

        // Generate and return the token
        return [
            'token'   => JWT::encode($payload, $this->setEncryptKey(), $this->setAlgorithm()),
            'id'      => $tokenId,
            'payload' => $payload,
        ];
    }

    public function decryptToken($token): array
    {
        try {
            // Decode the token
            $decodedToken = JWT::decode(
                $token,
                new Key($this->setDecryptKey(), $this->setAlgorithm())
            );

            // Validate token structure
            if (
                empty($decodedToken)
                || !is_int($decodedToken->uid ?? null)
                || !in_array($decodedToken->type ?? '', ['access', 'refresh'], true)
                || !is_string($decodedToken->id ?? null)
            ) {
                return [
                    'status'  => false,
                    'message' => 'Invalid token structure.',
                ];
            }

            // Additional expire time check
            if ($decodedToken->exp < time()) {
                return [
                    'status'  => false,
                    'message' => 'Token expired.',
                ];
            }

            // Add Issued-At Skew
            if ($decodedToken->iat > (time() + 60)) {
                return [
                    'status'  => false,
                    'message' => 'Invalid issued time.',
                ];
            }

            // Validate the 'iss' (Issuer) and 'aud' (Audience) claims
            if ($decodedToken->iss !== $this->config['iss'] || $decodedToken->aud !== $this->config['aud']) {
                return [
                    'status'  => false,
                    'message' => 'Invalid issuer or audience',
                ];
            }

            // Get and check user data from cache
            $user = $this->cacheService->getUser((int)$decodedToken->uid);
            if (empty($user)) {
                return [
                    'status'  => false,
                    'message' => 'User not found.',
                ];
            }

            // Determine token type and validate access
            $isValidAccessToken = (
                $decodedToken->type === 'access'
                && isset($user['access_keys'][$decodedToken->id])
            );

            // Determine token type and validate access
            $isValidRefreshToken = (
                $decodedToken->type === 'refresh'
                && isset($user['refresh_keys'][$decodedToken->id])
            );

            // Check token is valid or not
            if ($isValidAccessToken || $isValidRefreshToken) {
                return [
                    'status'  => true,
                    'id'      => $decodedToken->id,
                    'user_id' => $decodedToken->uid,
                    'type'    => $decodedToken->type,
                    'data'    => (array)$decodedToken,
                ];
            }

            return [
                'status'  => false,
                'message' => 'Token not valid!',
            ];
        } catch (Exception $e) {
            return [
                'status'  => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @throws RandomException
     */
    private function setTokenKey($params): string
    {
        $typePrefix = $params['type'] === 'refresh' ? 'r' : 'a';

        return hash(
            'sha256',
            sprintf('%s-%s-%s', $typePrefix, $params['account']['id'], bin2hex(random_bytes(8)))
        );
    }

    private function setTtl(array $params): int
    {
        $type = $params['type'] === 'refresh' ? 'exp_refresh' : 'exp_access';
        return (int)($this->config[$type] ?? 0);
    }

    private function setAlgorithm(): string
    {
        return ($this->config['type'] ?? '') === 'keys' ? 'RS256' : 'HS256';
    }

    private function setEncryptKey(): string
    {
        return $this->getKey('private_key');
    }

    private function setDecryptKey(): string
    {
        return $this->getKey('public_key');
    }

    private function getKey(string $keyType): string
    {
        if (
            ($this->config['type'] ?? '') === 'keys'
            && !empty($this->config[$keyType] ?? null)
        ) {
            return file_get_contents($this->config[$keyType]);
        }

        return $this->config['secret'];
    }

}