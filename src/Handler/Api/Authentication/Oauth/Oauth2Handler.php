<?php

declare(strict_types=1);

namespace Pi\User\Handler\Api\Authentication\Oauth;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Authentication\Oauth\Oauth2;
use Pi\User\Service\AccountService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
                    'key'     => 'invalid-authentication-data-please-try-again',
                ],
            ];
            return new EscapingJsonResponse($errorResponse, StatusCodeInterface::STATUS_UNAUTHORIZED);
        }


        // Check
        $authService = new Oauth2($this->config);
        $result      = $authService->verifyToken($requestBody);
        if (!$result['result']) {
            return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
        }

        // Do log in
        $result = $this->accountService->loginOauth2(array_merge($result['data'], ['security_stream' => $securityStream]));

        // Make a escaping json response
        $response = new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);

        // Set httponly cookie for access token and refresh token
        $cookies = $this->accountService->tokenCookies($result);
        foreach ($cookies as $cookie) {
            $response = $response->withAddedHeader('Set-Cookie', $cookie);
        }

        return $response;
    }
}