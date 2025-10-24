<?php

declare(strict_types=1);

namespace Pi\User\Service;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use phpseclib3\Crypt\RSA;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\Utility\Ip as IpUtility;
use Pi\Core\Service\Utility\Url as UrlUtility;
use Pi\Core\Service\UtilityService;
use Random\RandomException;
use RuntimeException;
use Throwable;

class TokenService implements ServiceInterface
{
    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /* @var array */
    protected array $config;

    protected string $internalKey = 'internal_tokens';

    public function __construct(
        CacheService   $cacheService,
        UtilityService $utilityService,
                       $config
    ) {
        $this->cacheService   = $cacheService;
        $this->utilityService = $utilityService;
        $this->config         = $config;

        // Validate config keys
        if (empty($this->config['private_key'])
            || empty($this->config['public_key'])
            || empty($this->config['internal_public_key'])
            || empty($this->config['internal_private_key'])
        ) {
            throw new RuntimeException('JWT private key and public key paths must be provided in config.');
        }

        // If either key is missing, regenerate both
        if (!file_exists($this->config['private_key']) || !file_exists($this->config['public_key'])) {
            $this->createKeys($this->config['private_key'], $this->config['public_key']);
        }

        // If either key is missing, regenerate both
        if (!file_exists($this->config['internal_private_key']) || !file_exists($this->config['internal_public_key'])) {
            $this->createKeys($this->config['internal_private_key'], $this->config['internal_public_key']);
        }
    }

    /**
     * @throws RandomException
     */
    public function addCustomToken($params, $operator): array
    {
        // Get a user fron redis cache
        $user = $this->cacheService->getUser((int)$params['user_id']);
        if (empty($user)) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'User not found',
                    'key'     => 'user-not-found',
                ],
            ];
        }

        // Generate internal token for a user
        $token = $this->encryptToken(
            [
                'account' => array_merge($user['account'], ['roles' => $user['roles']]),
                'type'    => 'access',
                'origin'  => 'internal',
                'ip'      => $params['ip'],
                'aud'     => $params['aud'],
                'ttl'     => $params['ttl'],
                'desc'    => $params['desc'],
            ]
        );

        // Set token to cache
        $user['access_keys'][$token['id']] = [
            'id'     => $token['id'],
            'create' => $token['payload']['iat'],
            'expire' => $token['payload']['exp'],
            'origin' => $token['payload']['origin'] ?? 'internal',
        ];

        // Update user cache
        $this->cacheService->setUserItem($user['account']['id'], 'access_keys', $user['access_keys']);

        // Update token list
        $tokens = $this->cacheService->getItem($this->internalKey);
        $this->cacheService->setItem($this->internalKey, array_merge($tokens, [$token['id'] => $token]));

        return [
            'result' => true,
            'data'   => $token,
            'error'  => [],
        ];
    }

    public function deleteCustomToken($params, $operator): array
    {
        return [];
    }

    public function getCustomTokenList(): array
    {
        return $this->cacheService->getItem($this->internalKey);
    }

    /**
     * @throws RandomException
     */
    public function encryptToken($params): array
    {
        // Generate a unique ID
        $tokenId = $params['id'] ?? $this->setTokenKey($params);
        $ttl     = $this->setTtl($params);

        // Build the payload
        $payload = [
            'id'     => $tokenId,
            'uid'    => $params['account']['id'],
            'iat'    => time(),
            'exp'    => time() + $ttl,
            'type'   => $params['type'],
            'iss'    => $this->config['iss'] ?? '',
            'aud'    => $params['aud'] ?? $this->config['aud'] ?? '',
            'sub'    => sprintf('user-%s', $params['account']['id']),
            'ip'     => $params['ip'],
            'origin' => $params['origin'] ?? 'public',
            'desc'   => $params['desc'] ?? '',
        ];

        // Add additional payload fields if configured
        if (isset($this->config['additional']) && !empty($this->config['additional'])) {
            foreach ($this->config['additional'] as $key) {
                $payload[$key] = $params['account'][$key] ?? '';
            }
        }

        // Generate and return the token
        return [
            'token'   => JWT::encode($payload, $this->setEncryptKey($params['origin']), $this->setAlgorithm()),
            'id'      => $tokenId,
            'payload' => $payload,
            'ttl'     => $ttl,
        ];
    }

    public function decryptToken($token, array $params = []): array
    {
        try {
            // Decode the token
            $decodedToken = JWT::decode(
                $token,
                new Key($this->setDecryptKey($params['origin']), $this->setAlgorithm())
            );

            // Validate token structure
            if (
                empty($decodedToken)
                || !is_int($decodedToken->uid ?? null)
                || !in_array($decodedToken->type ?? '', ['access', 'refresh'], true)
                || !in_array($decodedToken->origin ?? '', ['public', 'internal', 'local'], true)
                || !is_string($decodedToken->id ?? null)
            ) {
                return [
                    'status'  => false,
                    'message' => 'Invalid token structure.',
                    'key'     => 'invalid-token-structure',
                ];
            }

            // Additional expire time check
            if ($decodedToken->exp < time()) {
                return [
                    'status'  => false,
                    'message' => 'Token expired.',
                    'key'     => 'token-expired',
                ];
            }

            // Add Issued-At Skew
            if ($decodedToken->iat > (time() + 60)) {
                return [
                    'status'  => false,
                    'message' => 'Invalid issued time.',
                    'key'     => 'invalid-issued-time',
                ];
            }

            // Validate the 'iss' (Issuer)
            if ($decodedToken->iss !== $this->config['iss']) {
                return [
                    'status'  => false,
                    'message' => 'Invalid issuer',
                    'key'     => 'invalid-issuer',
                ];
            }

            // Validate the 'aud' (Audience) claims
            $url = new UrlUtility();
            if (!$url->isUrlAllowed($params['aud'], (array)$decodedToken->aud)) {
                return [
                    'status'  => false,
                    'message' => 'Invalid audience',
                    'key'     => 'invalid-audience',
                ];
            }

            // Check if the IP is valid or in the allowed list
            if (
                isset($this->config['check_ip'])
                && !empty($this->config['check_ip'])
                && isset($decodedToken->ip)
                && !empty($decodedToken->ip)
                && $decodedToken->type == 'access'
            ) {
                // Check user ip
                $ip = new IpUtility($this->config['ip']);
                if (!$ip->areIpsEqual($decodedToken->ip, $params['ip'])) {
                    return [
                        'status'  => false,
                        'message' => 'Invalid IP address',
                        'key'     => 'invalid-ip-address',
                    ];
                }
            }

            // Get and check user data from cache
            $user = $this->cacheService->getUser((int)$decodedToken->uid);
            if (empty($user)) {
                return [
                    'status'  => false,
                    'message' => 'User not found.',
                    'key'     => 'user-not-found',
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
                'key'     => 'token-not-valid',
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
        if (!empty($params['ttl'])) {
            return (int)$params['ttl'];
        }

        $type = $params['type'] === 'refresh' ? 'exp_refresh' : 'exp_access';
        return (int)($this->config[$type] ?? 0);
    }

    private function setAlgorithm(): string
    {
        return 'RS256';
    }

    private function setEncryptKey($origin): string
    {
        // If internal or local request, use internal key
        if ($origin === 'internal' || $origin === 'local') {
            return $this->getKey('internal_private_key');
        }

        // Otherwise, use standard private key
        return $this->getKey('private_key');
    }

    private function setDecryptKey($origin): string
    {
        // If internal or local request, use internal key
        if ($origin === 'internal' || $origin === 'local') {
            return $this->getKey('internal_public_key');
        }

        // Otherwise, use public key
        return $this->getKey('public_key');
    }

    private function getKey(string $keyType): string
    {
        return file_get_contents($this->config[$keyType]);
    }

    private function createKeys($privateKeyPath, $publicKeyPath): void
    {
        try {
            // Generate a 4096-bit RSA private key
            $privateKey = RSA::createKey(4096);
            $publicKey  = $privateKey->getPublicKey();

            // Save PEM-formatted keys
            if (!file_put_contents($privateKeyPath, $privateKey->toString('PKCS8'))) {
                throw new RuntimeException("Failed to save private key to {$privateKeyPath}");
            }

            if (!file_put_contents($publicKeyPath, $publicKey->toString('PKCS8'))) {
                throw new RuntimeException("Failed to save public key to {$publicKeyPath}");
            }
        } catch (Throwable $e) {
            // Log the error
            error_log("[ERROR] RSA Key Generation: " . $e->getMessage());
            throw new RuntimeException("Error generating RSA keys. Please check logs.");
        }
    }
}