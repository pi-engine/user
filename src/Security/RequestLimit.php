<?php

namespace User\Security;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use User\Service\CacheService;

class RequestLimit implements SecurityInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /* @var CacheService */
    protected CacheService $cacheService;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'requestLimit';

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        CacheService $cacheService,
        $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->cacheService    = $cacheService;
        $this->config          = $config;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $securityStream
     *
     * @return array
     */
    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        // Check if the IP is in the whitelist
        if (
            (bool)$this->config['requestLimit']['ignore_whitelist'] === true
            && isset($securityStream['ip']['data']['in_whitelist'])
            && (bool)$securityStream['ip']['data']['in_whitelist'] === true
        ) {
            return [
                'result' => true,
                'name'   => $this->name,
                'status' => 'ignore',
                'data'   => [],
            ];
        }

        // Get client ip
        $clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        // Set key
        $key = $this->sanitizeKey("rate_limit_{$clientIp}");

        // Get and check key
        $cacheData = $this->cacheService->getItem($key);
        if (empty($cacheData)) {
            $cacheData = ['count' => 1, 'timestamp' => time()];
            $this->cacheService->setItem($key, $cacheData, $this->config['requestLimit']['rate_limit']);
        } else {
            $cacheData = $this->validateCacheData($cacheData);
            if ($cacheData === false) {
                $cacheData = ['count' => 1, 'timestamp' => time(),];
                $this->cacheService->setItem($key, $cacheData);
            } else {
                // Update request count if within the rate limit window
                if ($cacheData['count'] >= $this->config['requestLimit']['max_requests']) {
                    return [
                        'result' => false,
                        'name'   => $this->name,
                        'status' => 'unsuccessful',
                        'data'   => [],
                    ];
                }
                $cacheData['count'] += 1;
                $this->cacheService->setItem($key, $cacheData);
            }
        }

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [],
        ];
    }

    /**
     * Sanitizes the cache key to ensure it meets the allowed format.
     *
     * @param string $key The original key
     *
     * @return string The sanitized key
     */
    private function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
    }

    /**
     * Validates the cache data to ensure it's still within the rate limit window.
     *
     * @param array $cacheData The cached data
     *
     * @return array|false Validated cache data or false if expired
     */
    private function validateCacheData(array $cacheData): bool|array
    {
        if ((time() - $cacheData['timestamp']) > $this->config['requestLimit']['rate_limit']) {
            return false;
        }
        return $cacheData;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Access denied: Rate limit exceeded. Please try again later';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }
}