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

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        AccountService $accountService,
        TokenService $tokenService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->tokenService    = $tokenService;
        $this->errorHandler    = new ErrorHandler($this->responseFactory, $this->streamFactory);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get token
        $token = $request->getHeaderLine('token');

        // get route match
        $routeMatch = $request->getAttribute('Laminas\Router\RouteMatch');

        // Check token set
        if (empty($token)) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute('error',
                [
                    'message' => 'Token is not set !',
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // parse token
        $tokenParsed = $this->tokenService->parse($token);

        // Check parsed token
        if (!$tokenParsed['status']) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute('error',
                [
                    'message' => $tokenParsed['message'],
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Check token type
        $type = ($routeMatch->getMatchedRouteName() == 'user/refresh') ? 'refresh' : 'access';
        if ($tokenParsed['type'] != $type) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute('error',
                [
                    'message' => 'This token not allowed for authentication',
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Get account
        $account = $this->accountService->getAccount(
            [
                'id'     => $tokenParsed['user_id'],
                'status' => 1,
            ]
        );

        // Check user is found
        if (empty($account)) {
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
        $request = $request->withAttribute('account', $account);
        $request = $request->withAttribute('token_id', $tokenParsed['id']);
        return $handler->handle($request);
    }
}