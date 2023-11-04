<?php

namespace User\Handler\Admin\Profile;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Service\AccountService;

class EditHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var AccountService */
    protected AccountService $accountService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        AccountService $accountService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBody    = $request->getParsedBody();
        $account        = $this->accountService->getAccount(['id' => (int)$requestBody['user_id']]);
        $operator        = $request->getAttribute('account');
        $updatedAccount = $this->accountService->updateAccount($requestBody, $account,$operator);
        $this->accountService->addRoleAccountByAdmin($requestBody,$updatedAccount,$operator);
        return new JsonResponse(
            [
                'result' => true,
                'data'   => $updatedAccount,
                'error'  => [],
            ]
        );
    }
}