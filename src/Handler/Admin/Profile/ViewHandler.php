<?php

namespace Pi\User\Handler\Admin\Profile;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\AccountService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ViewHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var AccountService */
    protected AccountService $accountService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        AccountService           $accountService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBody = $request->getParsedBody();

        $account = $this->accountService->getAccount(['id' => (int)$requestBody['user_id']]);
        $account = $this->accountService->viewAccount($account);

        // Set result array
        $result = [
            'result' => true,
            'data'   => $account,
            'error'  => [],
        ];

        return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}