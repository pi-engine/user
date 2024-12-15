<?php

namespace Pi\User\Handler\Admin\Cache;

use Pi\Core\Response\EscapingJsonResponse;
use Pi\Core\Service\CacheService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var CacheService */
    protected CacheService $cacheService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        CacheService             $cacheService,
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->cacheService    = $cacheService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Get role list
        $list = $this->cacheService->getCacheList();

        $result
            = [
            'result' => true,
            'data'   => [
                'list' => $list,
            ],
            'error'  => new \stdClass(),
        ];
        return new EscapingJsonResponse($result);
    }
}
