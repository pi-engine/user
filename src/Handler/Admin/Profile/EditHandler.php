<?php

declare(strict_types=1);

namespace Pi\User\Handler\Admin\Profile;

use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\AccountService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
        $operator    = $request->getAttribute('account');

        // Get account
        $account = $this->accountService->getAccount(['id' => (int)$requestBody['user_id']]);

        // Update account
        $updatedAccount = $this->accountService->updateAccount($requestBody, $account, $operator);

        // Update account role
        if (isset($requestBody['roles']) && !empty($requestBody['roles'])) {
            $this->accountService->updateAccountRoles($requestBody['roles'], $updatedAccount, 'all', $operator);
        }

        return new EscapingJsonResponse(
            [
                'result' => true,
                'data'   => $updatedAccount,
                'error'  => [],
            ]
        );
    }
}