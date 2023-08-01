<?php

namespace User\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Handler\ErrorHandler;
//use User\Security\Method as SecurityMethod;
use User\Security\Xss as SecurityXss;

class SecurityMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ErrorHandler $errorHandler
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->errorHandler    = $errorHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Start security checks in request
        foreach ($this->securityList() as $security) {
            if (!$security->check($request)) {
                $request = $request->withAttribute('status', $security->getStatusCode());
                $request = $request->withAttribute('error',
                    [
                        'message' => $security->getErrorMessage(),
                        'code'    => $security->getStatusCode(),
                    ]
                );
                return $this->errorHandler->handle($request);
            }
        }

        return $handler->handle($request);
    }

    protected function securityList(): array
    {
        return [
            //'method' => new SecurityMethod(),
            'xss'    => new SecurityXss(
                $this->responseFactory,
                $this->streamFactory
            ),
        ];
    }
}