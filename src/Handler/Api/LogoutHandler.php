<?php

namespace User\Handler\Api;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Service\AccountService;
use User\Service\TokenService;

class LogoutHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var AccountService */
    protected AccountService $accountService;

    /** @var TokenService */
    protected TokenService $tokenService;

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
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tokenId     = $request->getAttribute('token_id');
        $account     = $request->getAttribute('account');
        $requestBody = $request->getParsedBody();

        $params = [
            'user_id'     => $account['id'],
            'token_id'    => $tokenId,
            'all_session' => $requestBody['all_session'] ?? 0,
        ];

        $result = $this->accountService->logout($params);

        return new JsonResponse($result);
    }
}