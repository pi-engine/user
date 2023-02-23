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

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        AccountService $accountService,
        TokenService $tokenService,
        CacheService $cacheService,
        ErrorHandler $errorHandler
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->tokenService    = $tokenService;
        $this->cacheService    = $cacheService;
        $this->errorHandler    = $errorHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get token
        $token = $request->getHeaderLine('token');

        // get route match
        $routeMatch = $request->getAttribute('Laminas\Router\RouteMatch');
        $routeParams = $routeMatch->getParams();

        // Check token set
        if (empty($token)) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_UNAUTHORIZED);
            $request = $request->withAttribute('error',
                [
                    'message' => 'Token is not set !',
                    'code'    => StatusCodeInterface::STATUS_UNAUTHORIZED,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // parse token
        $tokenParsed = $this->tokenService->parse($token);

        // Check parsed token
        if (!$tokenParsed['status']) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_UNAUTHORIZED);
            $request = $request->withAttribute('error',
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

        // Check token type
        if ($tokenParsed['type'] != $type) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_UNAUTHORIZED);
            $request = $request->withAttribute('error',
                [
                    'message' => 'This token not allowed for authentication',
                    'code'    => StatusCodeInterface::STATUS_UNAUTHORIZED,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Get account data from cache
        $user = $this->cacheService->getUser($tokenParsed['user_id']);

        // Check user is found
        if (empty($user['account'])) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_UNAUTHORIZED);
            $request = $request->withAttribute('error',
                [
                    'message' => 'No user information found by this token !',
                    'code'    => StatusCodeInterface::STATUS_UNAUTHORIZED,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Set attribute
        $request = $request->withAttribute('account', $user['account']);
        $request = $request->withAttribute('roles', $user['roles']);
        $request = $request->withAttribute('token_id', $tokenParsed['id']);
        return $handler->handle($request);
    }
}