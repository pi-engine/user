<?php

namespace User\Handler\Api;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Service\AccountService;
use User\Service\TokenService;

class RegisterHandler implements RequestHandlerInterface
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
        $requestBody = $request->getParsedBody();

        $account = $this->accountService->addAccount($requestBody);

        if (!empty($account)) {
            $result = [
                'result' => true,
                'data'   => $account,
                'error'  => [],
            ];
        } else {
            // ToDo: use error handler for this part
            $result = [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error to register user account',
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ],
            ];
        }

        return new JsonResponse($result);
    }
}