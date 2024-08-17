<?php

namespace User\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Handler\ErrorHandler;
use User\Security\InputSizeLimit as SecurityInputSizeLimit;
use User\Security\InputValidation as SecurityInputValidation;
use User\Security\Ip as SecurityIp;
use User\Security\Method as SecurityMethod;
use User\Security\RequestLimit as SecurityRequestLimit;
use User\Security\Xss as SecurityXss;
use User\Service\CacheService;

class SecurityMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    /* @var array */
    protected array $config;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        CacheService $cacheService,
        ErrorHandler $errorHandler,
        $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->cacheService    = $cacheService;
        $this->errorHandler    = $errorHandler;
        $this->config          = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Start security checks in request
        $securityStream = [];
        foreach ($this->securityList() as $key => $security) {
            $securityStream[$key] = $security->check($request, $securityStream);
            if (!$securityStream[$key]['result']) {
                $request = $request->withAttribute('status', $security->getStatusCode());
                $request = $request->withAttribute(
                    'error',
                    [
                        'message' => $security->getErrorMessage(),
                        'code'    => $security->getStatusCode(),
                    ]
                );
                return $this->errorHandler->handle($request);
            }
        }

        $request = $request->withAttribute('security_stream', $securityStream);
        return $handler->handle($request);
    }

    protected function securityList(): array
    {
        $list = [];
        if (isset($this->config['ip']['is_active']) && $this->config['ip']['is_active']) {
            $list['ip'] = new SecurityIp($this->responseFactory, $this->streamFactory, $this->cacheService, $this->config);
        }
        if (isset($this->config['method']['is_active']) && $this->config['method']['is_active']) {
            $list['method'] = new SecurityMethod($this->config);
        }
        if (isset($this->config['inputSizeLimit']['is_active']) && $this->config['inputSizeLimit']['is_active']) {
            $list['inputSizeLimit'] = new SecurityInputSizeLimit($this->responseFactory, $this->streamFactory, $this->config);
        }
        if (isset($this->config['requestLimit']['is_active']) && $this->config['requestLimit']['is_active']) {
            $list['requestLimit'] = new SecurityRequestLimit($this->responseFactory, $this->streamFactory, $this->cacheService, $this->config);
        }
        if (isset($this->config['xss']['is_active']) && $this->config['xss']['is_active']) {
            $list['xss'] = new SecurityXss($this->responseFactory, $this->streamFactory, $this->config);
        }
        if (isset($this->config['inputValidation']['is_active']) && $this->config['inputValidation']['is_active']) {
            $list['inputValidation'] = new SecurityInputValidation($this->responseFactory, $this->streamFactory, $this->config);
        }

        return $list;
    }
}