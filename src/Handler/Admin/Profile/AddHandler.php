<?php

namespace User\Handler\Admin\Profile;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Service\AccountService;

class AddHandler implements RequestHandlerInterface
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
    )
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->accountService = $accountService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBody = $request->getParsedBody();
        $operator = $request->getAttribute('account');
        $result = $this->accountService->addAccount($requestBody, $operator);
        if (isset($result['id']) && !empty($result)) {
            $this->accountService->addRoleAccountByAdmin($requestBody, $result, $operator);
        } else {
            return new JsonResponse($result);
        }

        return new JsonResponse(
            [
                'result' => true,
                'data' => $result,
                'error' => [],
            ]
        );
    }
}