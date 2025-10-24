<?php

declare(strict_types=1);

namespace Pi\User\Handler\Admin\Token;

use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\TokenService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DeleteHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var TokenService */
    protected TokenService $tokenService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        TokenService              $tokenService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->tokenService     = $tokenService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBody = $request->getParsedBody();
        $operator    = $request->getAttribute('account');
        $result      = $this->tokenService->deleteCustomToken($requestBody, $operator);

        return new EscapingJsonResponse(
            [
                'result' => true,
                'data'   => $result,
                'error'  => [],
            ]
        );
    }
}