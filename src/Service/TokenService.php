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
use Random\RandomException;
use RuntimeException;
use Throwable;

class TokenService implements ServiceInterface
{
    /* @var CacheService */
    private CacheService $cacheService;

    /* @var array */
    private array $config;

    private string $internalKey = 'internal_tokens';

    private string $privateKey;
    private string $publicKey;
    private string $internalPrivateKey;
    private string $internalPublicKey;

    public function __construct(
        CacheService $cacheService,
                     $config
    ) {
        $this->cacheService = $cacheService;
        $this->config       = $config;

        $this->loadKeys();
    }

    /**
     * Generate and register a new internal custom access token for a specific user.
     *
     * This method retrieves the user's cached data from Redis, generates an internal
     * (non-public) access token using RSA signing, stores the token in both the user’s
     * access list and a global internal token registry, and returns the token payload.
     *
     * @param array $params    Token generation parameters including:
     *                         - user_id (int): Target user ID
     *                         - ip (string): Request IP address
     *                         - aud (string): Token audience (optional)
     *                         - ttl (int): Token lifetime in seconds (optional)
     *                         - desc (string): Optional token description
     * @param array $operator  Operator or service creating the token (for audit purposes)
     *
     * @return array{
     *     result: bool,
     *     data: array,
     *     error: array{message?: string, key?: string}
     * }
     *
     * @throws RandomException If random key generation fails.
     */
    public function addCustomToken(array $params, array $operator): array
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

    /**
     * Delete an existing custom token from cache.
     *
     * Intended for future implementation. This method should locate and remove
     * a specific internal access token based on provided parameters.
     *
     * @param array $params   Parameters identifying the token (e.g. token ID, user ID).
     * @param array $operator Operator or service performing the deletion.
     *
     * @return array{
     *     result: bool,
     *     data: array,
     *     error: array{message?: string, key?: string}
     * }
     */
    public function deleteCustomToken(array $params, array $operator): array
    {
        return [];
    }

    /**
     * Retrieve the list of all active internal custom tokens.
     *
     * This method fetches the internal token registry from cache, containing
     * all tokens generated through {@see addCustomToken()}.
     *
     * @return array<string, array> Associative array of tokens indexed by token ID.
     */
    public function getCustomTokenList(): array
    {
        return $this->cacheService->getItem($this->internalKey);
    }

    /**
     * Encrypt (sign) a new JWT token for an authenticated user.
     *
     * This method builds a secure JWT payload, applies origin-based key selection,
     * and returns the signed token with metadata.
     *
     * @param array $params  {
     *
     * @type array  $account User account data (must include 'id')
     * @type string $type    Token type: 'access' or 'refresh'
     * @type string $origin  Token origin: 'public', 'internal', or 'local'
     * @type string $ip      IP address of the requester
     * @type string $desc    Optional description
     * @type string $aud     Optional audience
     * @type string $id      Optional token ID (if not auto-generated)
     * @type int    $ttl     Optional TTL override
     *                       }
     *
     * @return array{
     *     token: string,
     *     id: string,
     *     payload: array,
     *     ttl: int
     * }
     *
     * @throws RandomException
     * @throws RuntimeException if key loading or signing fails
     */
    public function encryptToken(array $params): array
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

    /**
     * Decrypt (verify) a JWT and validate its structure and claims.
     *
     * This method ensures the JWT is properly signed, unexpired, matches
     * issuer/audience expectations, and references a valid user session.
     *
     * @param string $token  The raw JWT string.
     * @param array  $params {
     *
     * @type string  $origin Token origin (required for key selection)
     * @type string  $ip     Requester IP for IP validation (optional)
     * @type string  $aud    Audience value for validation (optional)
     *                       }
     *
     * @return array{
     *     status: bool,
     *     id?: string,
     *     user_id?: int,
     *     type?: string,
     *     data?: array,
     *     message?: string,
     *     key?: string
     * }
     */
    public function decryptToken(string $token, array $params = []): array
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
            if (
                isset($this->config['check_aud'])
                && !empty($this->config['check_aud'])
                && isset($decodedToken->aud)
                && !empty($decodedToken->aud)
                && $decodedToken->type == 'access'
            ) {
                $url = new UrlUtility();
                if (!$url->isUrlAllowed($params['aud'], (array)$decodedToken->aud)) {
                    return [
                        'status'  => false,
                        'message' => sprintf('Invalid audience, requested audience is : %s token audience: %s', $params['aud'], $decodedToken->aud),
                        'key'     => 'invalid-audience',
                    ];
                }
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
                        'message' => sprintf('Invalid IP address, requested audience is : %s token audience: %s', $params['ip'], $decodedToken->ip),
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
     * Generates a unique SHA-256 token key identifier for a JWT.
     *
     * The key is derived from the token type (access/refresh), the associated
     * account ID, and a securely generated random salt. This ensures that
     * each token — even for the same account — is cryptographically unique.
     *
     * Example output:
     *   a-42-fd82ac0a9134de9a
     *
     * @param array{
     *     type: string,
     *     account: array{id: int|string}
     * } $params Token metadata.
     *
     * @return string The generated token key hash (SHA-256).
     * @throws RandomException If secure random bytes cannot be generated.
     *
     */
    private function setTokenKey(array $params): string
    {
        $typePrefix = $params['type'] === 'refresh' ? 'r' : 'a';

        return hash(
            'sha256',
            sprintf('%s-%s-%s', $typePrefix, $params['account']['id'], bin2hex(random_bytes(8)))
        );
    }

    /**
     * Determines the token's time-to-live (TTL) in seconds.
     *
     * If the `$params` array includes a custom `ttl` value, that value
     * is used. Otherwise, the default expiration time is read from
     * the configuration array, using either `exp_access` or `exp_refresh`
     * depending on token type.
     *
     * @param array{
     *     type: string,
     *     ttl?: int
     * } $params Token metadata.
     *
     * @return int TTL value in seconds.
     */
    private function setTtl(array $params): int
    {
        if (!empty($params['ttl'])) {
            return (int)$params['ttl'];
        }

        $type = $params['type'] === 'refresh' ? 'exp_refresh' : 'exp_access';
        return (int)($this->config[$type] ?? 0);
    }

    /**
     * Returns the algorithm used for JWT signing and verification.
     *
     * Currently, this implementation uses RSA SHA-256 (RS256) for asymmetric
     * signing operations. If a different algorithm is required, this method
     * should be adapted accordingly.
     *
     * @return string The JWT signing algorithm (default: RS256).
     */
    private function setAlgorithm(): string
    {
        return 'RS256';
    }

    /**
     * Returns the appropriate private key used for signing JWTs.
     *
     * This method selects between the standard and internal RSA private keys
     * based on the request origin. Internal or local service calls (such as
     * microservice-to-microservice communication) use a separate internal key
     * pair for stronger isolation and security boundaries.
     *
     * @param string $origin The origin of the request (e.g. "internal", "local", or "external").
     *
     * @return string PEM-formatted private key for JWT signing.
     */
    private function setEncryptKey(string $origin): string
    {
        // If internal or local request, use internal key
        if (!empty($this->config['use_origin']) && in_array($origin, ['internal', 'local'], true)) {
            return $this->internalPrivateKey;
        }

        // Otherwise, use standard private key
        return $this->privateKey;
    }

    /**
     * Returns the appropriate public key used for verifying JWTs.
     *
     * This method mirrors {@see setEncryptKey()} by choosing the correct
     * public key based on request origin. Internal or local requests will use
     * the internal public key to validate tokens signed by internal services.
     *
     * @param string $origin The origin of the request (e.g. "internal", "local", or "external").
     *
     * @return string PEM-formatted public key for JWT verification.
     */
    private function setDecryptKey(string $origin): string
    {
        // If internal or local request, use internal key
        if (!empty($this->config['use_origin']) && in_array($origin, ['internal', 'local'], true)) {
            return $this->internalPublicKey;
        }

        // Otherwise, use standard public key
        return $this->publicKey;
    }

    /**
     * Loads RSA signing and verification keys used for JWT operations.
     *
     * This method first attempts to load PEM-formatted keys injected by EnvKeys
     * (via environment variables such as `PRIVATE_KEY_PEM` and `INTERNAL_PRIVATE_KEY_PEM`).
     *
     * If EnvKeys keys are not present, it falls back to reading the keys from configured
     * file paths (e.g., `private_key`, `public_key`, `internal_private_key`, `internal_public_key`).
     *
     * When key files are missing, new RSA key pairs are automatically generated and stored
     * using {@see self::loadOrCreateKeyPair()}.
     *
     * The method also validates all loaded keys using OpenSSL to ensure they are valid
     * and correctly formatted PEM strings.
     *
     * @return void
     * @throws RuntimeException If any required key is missing, malformed, or fails validation.
     *
     */
    private function loadKeys(): void
    {
        // Load possible EnvKeys-injected PEM keys
        $envKeys = [
            'private'          => getenv('PRIVATE_KEY_PEM') ?: null,
            'public'           => getenv('PUBLIC_KEY_PEM') ?: null,
            'internal_private' => getenv('INTERNAL_PRIVATE_KEY_PEM') ?: null,
            'internal_public'  => getenv('INTERNAL_PUBLIC_KEY_PEM') ?: null,
        ];

        // Normalize newline characters
        foreach ($envKeys as $key => $value) {
            if ($value !== null) {
                $envKeys[$key] = str_replace(['\\n', "\r"], "\n", $value);
            }
        }

        // Main JWT key pair
        if (!empty($envKeys['private']) || !empty($envKeys['public'])) {
            if (
                empty($envKeys['private'])
                || empty($envKeys['public'])
                || !openssl_pkey_get_private($envKeys['private'])
                || !openssl_pkey_get_public($envKeys['public'])
            ) {
                throw new RuntimeException('InvalidEnvKeysKeys: EnvKeys-injected JWT keys are missing or invalid.');
            }

            $this->privateKey = $envKeys['private'];
            $this->publicKey  = $envKeys['public'];
        } else {
            $keys             = $this->loadOrCreateKeyPair('private_key', 'public_key');
            $this->privateKey = $keys['private'];
            $this->publicKey  = $keys['public'];
        }

        // Internal JWT key pair
        if (!empty($envKeys['internal_private']) || !empty($envKeys['internal_public'])) {
            if (
                empty($envKeys['internal_private'])
                || empty($envKeys['internal_public'])
                || !openssl_pkey_get_private($envKeys['internal_private'])
                || !openssl_pkey_get_public($envKeys['internal_public'])
            ) {
                throw new RuntimeException('InvalidEnvKeysKeys: EnvKeys-injected INTERNAL JWT keys are missing or invalid.');
            }

            $this->internalPrivateKey = $envKeys['internal_private'];
            $this->internalPublicKey  = $envKeys['internal_public'];
        } else {
            $keys                     = $this->loadOrCreateKeyPair('internal_private_key', 'internal_public_key');
            $this->internalPrivateKey = $keys['private'];
            $this->internalPublicKey  = $keys['public'];
        }
    }

    /**
     * Loads an RSA key pair from configured file paths, or creates new ones if missing.
     *
     * This method checks the provided config entries for the private and public key paths.
     * If the specified key files do not exist, new RSA keys are generated and saved using
     * {@see self::createKeys()}. The method then returns both keys as PEM-formatted strings.
     *
     * @param string $privateKeyConfig The configuration key name for the private key path.
     * @param string $publicKeyConfig  The configuration key name for the public key path.
     *
     * @return array{private: string, public: string} Associative array containing the loaded
     *                                               private and public PEM key contents.
     * @throws RuntimeException If configuration paths are missing, unreadable, or file operations fail.
     *
     */
    private function loadOrCreateKeyPair(string $privateKeyConfig, string $publicKeyConfig): array
    {
        $privatePath = $this->config[$privateKeyConfig] ?? null;
        $publicPath  = $this->config[$publicKeyConfig] ?? null;

        if (empty($privatePath) || empty($publicPath)) {
            throw new RuntimeException("Missing key paths in config: {$privateKeyConfig}, {$publicKeyConfig}");
        }

        if (!file_exists($privatePath) || !file_exists($publicPath)) {
            $this->createKeys($privatePath, $publicPath);
        }

        return [
            'private' => file_get_contents($privatePath),
            'public'  => file_get_contents($publicPath),
        ];
    }

    /**
     * Generates a new RSA key pair and saves them as PEM files.
     *
     * This method creates a 4096-bit RSA private key using phpseclib's RSA library,
     * derives the corresponding public key, and writes both keys to the specified
     * file paths in PKCS8 PEM format. If any file operation fails or an exception
     * occurs during key generation, a RuntimeException is thrown.
     *
     * @param string $privateKeyPath Absolute or relative file path where the private key will be saved.
     * @param string $publicKeyPath  Absolute or relative file path where the public key will be saved.
     *
     * @return void
     * @throws RuntimeException If key generation fails or files cannot be written.
     *
     */
    private function createKeys(string $privateKeyPath, string $publicKeyPath): void
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