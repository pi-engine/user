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

class LogoutHandler implements RequestHandlerInterface
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
        $tokenId        = $request->getAttribute('token_id');
        $account        = $request->getAttribute('account');
        $securityStream = $request->getAttribute('security_stream');
        $requestBody    = $request->getParsedBody();

        $params = [
            'user_id'     => $account['id'],
            'token_id'    => $tokenId,
            'all_session' => $requestBody['all_session'] ?? 0,
        ];

        $result = $this->accountService->logout($params);

        // Make a escaping json response
        $response = new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);

        // Set httponly cookie for access token and refresh token
        $cookies = $this->accountService->unsetTokenCookies($result, $securityStream);
        foreach ($cookies as $cookie) {
            $response = $response->withAddedHeader('Set-Cookie', $cookie);
        }

        return $response;
    }
}