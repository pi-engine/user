<?php

declare(strict_types=1);

namespace Pi\User\Handler\Api\Authentication\Mobile;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\AccountService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerifyHandler implements RequestHandlerInterface
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
        $securityStream = $request->getAttribute('security_stream');
        $requestBody    = $request->getParsedBody();

        // Set login params
        $params = [
            'identityColumn'   => 'mobile',
            'credentialColumn' => 'otp',
            'identity'         => $requestBody['mobile'],
            'credential'       => $requestBody['otp'],
            'source'           => $requestBody['source'] ?? '',
            'security_stream'  => $securityStream,
        ];

        // Do log in
        $result = $this->accountService->login($params);

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