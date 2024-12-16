<?php

declare(strict_types=1);

namespace Pi\User\Handler\Api\Password;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\AccountService;
use Pi\User\Service\TokenService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UpdateHandler implements RequestHandlerInterface
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
        $requestBody = $request->getParsedBody();
        $account     = $request->getAttribute('account');
        $result      = $this->accountService->updatePassword($requestBody, $account);

        return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}
