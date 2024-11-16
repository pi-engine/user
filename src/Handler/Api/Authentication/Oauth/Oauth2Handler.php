<?php

namespace Pi\User\Handler\Api\Authentication\Oauth;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Pi\User\Authentication\Oauth\Oauth2;
use Pi\User\Service\AccountService;

class Oauth2Handler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var AccountService */
    protected AccountService $accountService;

    /* @var array */
    protected array $config;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        AccountService           $accountService,
                                 $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->config          = $config;
    }

    /**
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $securityStream = $request->getAttribute('security_stream');
        $requestBody    = $request->getParsedBody();

        if (!isset($requestBody['code'])) {
            $errorResponse = [
                'result' => false,
                'data'   => null,
                'error'  => [
                    'message' => 'Invalid authentication data. please try again!',
                ],
            ];
            return new JsonResponse($errorResponse, StatusCodeInterface::STATUS_UNAUTHORIZED);
        }


        // Check
        $authService = new Oauth2($this->config);
        $result      = $authService->verifyToken($requestBody);
        if (!$result['result']) {
            return new JsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
        }

        // Do log in
        $result = $this->accountService->loginOauth2(array_merge($result['data'], ['security_stream' => $securityStream]));

        return new JsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}