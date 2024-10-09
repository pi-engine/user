<?php

namespace User\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Handler\ErrorHandler;
use User\Security\Injection as SecurityInjection;
use User\Security\InputSizeLimit as SecurityInputSizeLimit;
use User\Security\InputValidation as SecurityInputValidation;
use User\Security\Ip as SecurityIp;
use User\Security\Method as SecurityMethod;
use User\Security\RequestLimit as SecurityRequestLimit;
use User\Security\Xss as SecurityXss;
use User\Service\CacheService;
use User\Service\UtilityService;

class SecurityMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    /* @var array */
    protected array $config;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        CacheService $cacheService,
        UtilityService $utilityService,
        ErrorHandler $errorHandler,
        $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->cacheService    = $cacheService;
        $this->utilityService  = $utilityService;
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

        // Set security attribute
        $request = $request->withAttribute('security_stream', $securityStream);

        // Call the next middleware or handler
        $response = $handler->handle($request);

        // Set security headers in response
        return $this->setSecurityHeader($response);
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
        if (isset($this->config['injection']['is_active']) && $this->config['injection']['is_active']) {
            $list['injection'] = new SecurityInjection($this->responseFactory, $this->streamFactory, $this->config);
        }
        if (isset($this->config['inputValidation']['is_active']) && $this->config['inputValidation']['is_active']) {
            $list['inputValidation'] = new SecurityInputValidation($this->responseFactory, $this->streamFactory, $this->config);
        }

        return $list;
    }

    protected function setSecurityHeader(ResponseInterface $response): ResponseInterface
    {
        return $response
            // Content Security Policy (CSP)
            ->withHeader(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';"
            )

            // Strict-Transport-Security (HSTS)
            ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload')

            // X-Content-Type-Options
            ->withHeader('X-Content-Type-Options', 'nosniff')

            // X-Frame-Options
            ->withHeader('X-Frame-Options', 'DENY')

            // Referrer-Policy
            ->withHeader('Referrer-Policy', 'no-referrer')

            // Permissions-Policy
            ->withHeader('Permissions-Policy', "geolocation=(), microphone=(), camera=(), fullscreen=(), payment=(), usb=(), vibrate=(), sync-xhr=()")

            // X-XSS-Protection
            ->withHeader('X-XSS-Protection', '1; mode=block')

            // X-Permitted-Cross-Domain-Policies
            ->withHeader('X-Permitted-Cross-Domain-Policies', 'none')

            // Cross-Origin Resource Sharing (CORS)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', $this->config['method']['allow_method'])
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, token')
            ->withHeader('Access-Control-Max-Age', '3600')

            // Expect-CT
            ->withHeader('Expect-CT', 'max-age=86400, enforce')

            // Cache-Control
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate')

            // X-Download-Options
            ->withHeader('X-Download-Options', 'noopen')

            // X-Powered-By
            ->withoutHeader('X-Powered-By');
    }
}