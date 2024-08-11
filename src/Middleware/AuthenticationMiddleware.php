<?php

namespace User\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Handler\ErrorHandler;
use User\Security\AccountLocked;
use User\Service\AccountService;
use User\Service\CacheService;
use User\Service\TokenService;

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

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    /* @var array */
    protected array $config;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        AccountService $accountService,
        TokenService $tokenService,
        CacheService $cacheService,
        AccountLocked $accountLocked,
        ErrorHandler $errorHandler,
        $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->tokenService    = $tokenService;
        $this->cacheService    = $cacheService;
        $this->accountLocked        = $accountLocked;
        $this->errorHandler    = $errorHandler;
        $this->config          = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get token
        $securityStream = $request->getAttribute('security_stream');
        $token = $request->getHeaderLine('token');

        // get route match
        $routeMatch  = $request->getAttribute('Laminas\Router\RouteMatch');
        $routeParams = $routeMatch->getParams();

        // Check a token set
        if (empty($token)) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_UNAUTHORIZED);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => 'Token is not set !',
                    'code'    => StatusCodeInterface::STATUS_UNAUTHORIZED,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // parse token
        $tokenParsed = $this->tokenService->decryptToken($token);

        // Check parsed token
        if (!$tokenParsed['status']) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_UNAUTHORIZED);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => $tokenParsed['message'],
                    'code'    => StatusCodeInterface::STATUS_UNAUTHORIZED,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Set token type
        $type = 'access';
        if (
            isset($routeParams['module'])
            && $routeParams['module'] == 'user'
            && isset($routeParams['handler'])
            && $routeParams['handler'] == 'refresh'
        ) {
            $type = 'refresh';
        }

        // Check a token type
        if ($tokenParsed['type'] != $type) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_UNAUTHORIZED);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => 'This token not allowed for authentication',
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
        if (empty($user['account'])) {
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
        if ($multiFactorGlobal && $routeParams['package'] != 'authentication') {
            if (
                !isset($user['multi_factor'][$tokenParsed['id']]['multi_factor_verify'])
                || (int)$user['multi_factor'][$tokenParsed['id']]['multi_factor_verify'] == 0
            ) {
                $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
                $request = $request->withAttribute(
                    'error',
                    [
                        'message'             => 'To complete your login, please enter the 6-digit code from your multi factor app.',
                        'code'                => StatusCodeInterface::STATUS_FORBIDDEN,
                        'multi_factor_global' => $multiFactorGlobal,
                        'multi_factor_status' => (int)$user['multi_factor'][$tokenParsed['id']]['multi_factor_status'],
                        'multi_factor_verify' => (int)$user['multi_factor'][$tokenParsed['id']]['multi_factor_verify'],
                    ]
                );
                return $this->errorHandler->handle($request);
            }
        }

        // Set attribute
        $request = $request->withAttribute('account', $user['account']);
        $request = $request->withAttribute('roles', $user['roles']);
        $request = $request->withAttribute('token_id', $tokenParsed['id']);
        $request = $request->withAttribute('current_token', $token);
        return $handler->handle($request);
    }
}