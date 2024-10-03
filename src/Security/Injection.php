<?php

namespace User\Security;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Injection implements SecurityInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'injection';

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
                                 $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
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
            (bool)$this->config['injection']['ignore_whitelist'] === true
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

        // Get request and query body
        $requestParams = $request->getParsedBody();
        $QueryParams   = $request->getQueryParams();
        $params        = array_merge($requestParams, $QueryParams);

        // Do check
        if (!empty($params)) {
            $injection = $this->detectSqlInjection($params);
            if ($injection) {
                return [
                    'result' => false,
                    'name'   => $this->name,
                    'status' => 'unsuccessful',
                    'data'   => [],
                ];
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
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Access denied: Injection detected';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }

    protected function detectSqlInjection(array $input): bool
    {
        $sqlInjectionPatterns = [
            // Basic SQL keywords
            '/\b(select|insert|update|delete|drop|create|alter|truncate|exec|execute|grant|revoke|declare)\b/i',

            // SQL comments and operators
            '/--/', // SQL single-line comment
            '/\/\*/', // SQL multi-line comment start
            '/\*\//', // SQL multi-line comment end
            '/\b(or|and)\s+1\s*=\s*1\b/i', // Boolean conditions
            '/\b(or|and)\s+\'[^\']*\'\s*=\s*\'[^\']*\'/i', // String comparisons
            '/\b(or|and)\s+\"[^\"]*\"\s*=\s*\"[^\"]*\"/i', // Double-quoted string comparisons

            // SQL functions and expressions
            '/\b(select|insert|update|delete|exec|execute|db_name|user|version|ifnull|sleep|benchmark)\b/i',
            '/\binformation_schema\b/i', // Targeting database metadata
            '/\bcase\s+when\b/i', // Case statements
            '/\bnull\b/i', // Handling null values

            // Unions and conditional operations
            '/\bunion\b/i',
            '/\bunion\s+all\b/i',
            '/\bexists\s*\(\s*select\b/i', // Checking for subquery existence

            // Other suspicious characters
            '/;/', // Statement terminators
            '/\bcast\b/i', // Cast functions
            '/\bconvert\b/i', // Convert functions
            '/\bdrop\s+database\b/i', // Dropping databases
            '/\bshutdown\b/i', // Attempting to shut down the database
            '/\bwaitfor\s+delay\b/i', // Time delay operations

            // Hex or binary injection
            '/\b0x[0-9a-fA-F]+\b/i', // Hexadecimal injection
            '/\bx\'[0-9a-fA-F]+\'/i', // Hex-encoded strings
            '/\b(b|x)[\'"]?[0-9a-fA-F]+[\'"]?/i', // Binary/hex literals

            // Miscellaneous suspicious patterns
            '/\b(select.*from|union.*select|insert.*into|update.*set|delete\s+from|drop\s+table|create\s+table|alter\s+table|truncate\s+table)\b/i',
            // Catching statements
            '/\b(or|and)\s+\d+\s*=\s*\d+/i', // Numeric equality checks
            '/\b(?:like|regexp)\b/i', // Check for pattern matching
            '/\b(if|case)\s*\(/i', // If/Case conditions
            '/\s*;\s*(select|insert|update|delete|drop|create|alter|truncate)\s+/i', // Chained queries
        ];

        // Helper method to recursively check for SQL injection
        foreach ($input as $value) {
            if ($this->checkValueForSqlInjection($value, $sqlInjectionPatterns)) {
                return true; // SQL injection detected
            }
        }

        return false; // No SQL injection detected
    }

    // Helper function to check a value or recursively check an array
    protected function checkValueForSqlInjection($value, array $patterns): bool
    {
        if (is_array($value)) {
            // Recursively check each element in the array
            foreach ($value as $subValue) {
                if ($this->checkValueForSqlInjection($subValue, $patterns)) {
                    return true; // SQL injection detected in sub-array
                }
            }
        } elseif (is_string($value)) {
            // Apply SQL injection patterns to string values
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return true; // SQL injection detected in string
                }
            }
        }

        return false; // No SQL injection detected
    }
}