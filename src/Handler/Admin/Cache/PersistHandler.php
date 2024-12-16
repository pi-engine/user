<?php

declare(strict_types=1);

namespace Pi\User\Handler\Admin\Cache;

use Pi\Core\Response\EscapingJsonResponse;
use Pi\Core\Service\CacheService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PersistHandler implements RequestHandlerInterface
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
        $requestBody = $request->getParsedBody();

        // Get cche item
        $this->cacheService->setPersist((string)$requestBody['key']);

        $result
            = [
            'result' => true,
            'data'   => [
                'message' => 'Cache persist set !',
            ],
            'error'  => new \stdClass(),
        ];
        return new EscapingJsonResponse($result);
    }
}
