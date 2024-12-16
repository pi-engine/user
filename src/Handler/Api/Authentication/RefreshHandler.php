<?php

declare(strict_types=1);

namespace Pi\User\Handler\Api\Authentication;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\AccountService;
use Pi\User\Service\TokenService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RefreshHandler implements RequestHandlerInterface
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
        StreamFactoryInterface   $streamFactory,
        AccountService           $accountService,
        TokenService             $tokenService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->tokenService    = $tokenService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $account = $request->getAttribute('account');

        $params = [
            'user_id' => $account['id'],
        ];

        //$result = $this->accountService->refreshToken($params);
        $result = [
            'result' => false,
            'data'   => [],
            'error'  => [
                'message' => 'Refresh user token is forbidden !',
            ],
            'status' => StatusCodeInterface::STATUS_FORBIDDEN,
        ];

        // Set result
        return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}
