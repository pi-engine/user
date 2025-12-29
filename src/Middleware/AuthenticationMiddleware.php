<?php

declare(strict_types=1);

namespace Pi\User\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Handler\ErrorHandler;
use Pi\Core\Security\Account\AccountLocked;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\UtilityService;
use Pi\User\Service\AccountService;
use Pi\User\Service\TokenService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var AccountService */
    protected AccountService $accountService;

    /** @var TokenService */
    protected TokenService $tokenService;

    /** @var CacheService */
    protected CacheService $cacheService;

    /** @var AccountLocked */
    protected AccountLocked $accountLocked;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    /* @var array */
    protected array $config;

    protected string $accessToken = '';
    protected string $refreshToken = '';

    protected string $typeToken = '';

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        AccountService           $accountService,
        TokenService             $tokenService,
        CacheService             $cacheService,
        AccountLocked            $accountLocked,
        UtilityService           $utilityService,
        ErrorHandler             $errorHandler,
                                 $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->tokenService    = $tokenService;
        $this->cacheService    = $cacheService;
        $this->accountLocked   = $accountLocked;
        $this->utilityService  = $utilityService;
        $this->errorHandler    = $errorHandler;
        $this->config          = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get security stream
        $securityStream = $request->getAttribute('security_stream');

        // Set token
        $token = $this->resolveToken($request);

        // Check a token set
        if (empty($token)) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_UNAUTHORIZED);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => 'Token is not set !',
                    'key'     => 'token-is-not-set',
                    'code'    => StatusCodeInterface::STATUS_UNAUTHORIZED,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // parse token
        $tokenParsed = $this->tokenService->decryptToken(
            $token,
            [
                'ip'     => $securityStream['ip']['data']['client_ip'],
                'aud'    => $securityStream['url']['data']['client_url'],
                'origin' => $securityStream['origin']['data']['origin'],
            ]
        );

        // Check parsed token
        if (!$tokenParsed['status'] || $tokenParsed['type'] !== $this->typeToken) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_UNAUTHORIZED);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => $tokenParsed['message'] ?? 'Invalid token !',
                    'key'     => $tokenParsed['key'] ?? 'invalid-token',
                    'code'    => StatusCodeInterface::STATUS_UNAUTHORIZED,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Check account is lock or not
        if ($this->accountLocked->isLocked(['type' => 'id', 'user_id' => (int)$tokenParsed['user_id'], 'security_stream' => $securityStream])) {
            $request = $request->withAttribute('status', $this->accountLocked->getStatusCode());
            $request = $request->withAttribute(
                'error',
                [
                    'message' => $this->accountLocked->getErrorMessage(),
                    'code'    => $this->accountLocked->getStatusCode(),
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Get account data from cache
        $user = $this->cacheService->getUser($tokenParsed['user_id']);

        // Check user is found
        if (!$user || empty($user['account'])) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_UNAUTHORIZED);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => 'No user information found by this token !',
                    'code'    => StatusCodeInterface::STATUS_UNAUTHORIZED,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Check multi factor
        $multiFactorGlobal = (int)$this->config['multi_factor']['status'] ?? 0;
        if ($multiFactorGlobal
            && $routeParams['package'] !== 'authentication'
            && (int)$user['multi_factor'][$tokenParsed['id']]['multi_factor_verify'] !== 1
        ) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute(
                'error',
                [
                    'message'             => 'To complete your login, please enter the 6-digit code from your multi factor app.',
                    'code'                => StatusCodeInterface::STATUS_FORBIDDEN,
                    'multi_factor_global' => $multiFactorGlobal,
                    'multi_factor_status' => (int)$user['multi_factor'][$tokenParsed['id']]['multi_factor_status'],
                    'multi_factor_method' => (int)$user['multi_factor'][$tokenParsed['id']]['multi_factor_method'],
                    'multi_factor_verify' => (int)$user['multi_factor'][$tokenParsed['id']]['multi_factor_verify'],
                ]
            );
            return $this->errorHandler->handle($request);
        } elseif (
            isset($user['account']['multi_factor_status'])
            && (int)$user['account']['multi_factor_status'] === 1
            && (int)$user['multi_factor'][$tokenParsed['id']]['multi_factor_verify'] !== 1
            && $routeParams['package'] !== 'authentication'
        ) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute(
                'error',
                [
                    'message'             => 'To complete your login, please enter the 6-digit code from your multi factor app. 2',
                    'code'                => StatusCodeInterface::STATUS_FORBIDDEN,
                    'multi_factor_global' => $multiFactorGlobal,
                    'multi_factor_status' => (int)$user['multi_factor'][$tokenParsed['id']]['multi_factor_status'],
                    'multi_factor_method' => (int)$user['multi_factor'][$tokenParsed['id']]['multi_factor_method'],
                    'multi_factor_verify' => (int)$user['multi_factor'][$tokenParsed['id']]['multi_factor_verify'],
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Update user online list
        $this->accountService->updateUserOnline($user['account']['id'], $tokenParsed['id'], $securityStream['ip']['data']['client_ip']);

        // Set attribute
        return $handler->handle(
            $request
                ->withAttribute('account', $user['account'])
                ->withAttribute('roles', $user['roles'])
                ->withAttribute('token_id', $tokenParsed['id'])
                ->withAttribute('token_data', $tokenParsed['data'])
                ->withAttribute('current_token', $token)
        );
    }

    private function resolveToken(ServerRequestInterface $request): ?string
    {
        // Get route parameters safely
        $routeMatch  = $request->getAttribute('Laminas\Router\RouteMatch');
        $routeParams = $routeMatch->getParams();

        // Get headers and cookies
        $authorizationHeader = $request->getHeaderLine('Authorization');
        $tokenHeader         = $request->getHeaderLine('token');
        $refreshHeader       = $request->getHeaderLine('refresh-token');
        $cookies             = $request->getCookieParams();

        // Helper to extract Bearer token
        $getBearerToken = fn(string $header) => preg_match('/^Bearer\s+(.+)$/i', $header, $matches) ? $matches[1] : $header;

        // Resolve access token (priority: cookie > Authorization header > token header)
        $this->accessToken = $cookies['Authorization'] ?? (
        !empty($authorizationHeader) ? $getBearerToken($authorizationHeader) : ($tokenHeader ?? null)
        );

        // Resolve refresh token (priority: cookie > refresh header)
        $this->refreshToken = $cookies['refresh-token'] ?? ($refreshHeader ?? null);

        // Determine token type
        $this->typeToken = $this->isValidRefreshToken($routeParams) ? 'refresh' : 'access';

        // Return the appropriate token
        return match ($this->typeToken) {
            'access'  => $this->accessToken,
            'refresh' => $this->refreshToken,
            default   => null,
        };
    }

    private function isValidRefreshToken(array $routeParams): bool
    {
        return !empty($this->refreshToken)
            && isset($routeParams['module'], $routeParams['handler'])
            && in_array($routeParams['module'], ['user', 'company'], true)
            && $routeParams['handler'] === 'refresh';
    }
}