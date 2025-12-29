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
use Random\RandomException;

class RefreshHandler implements RequestHandlerInterface
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

    /**
     * @throws RandomException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $account        = $request->getAttribute('account');
        $roles          = $request->getAttribute('roles');
        $tokenId        = $request->getAttribute('token_id');
        $tokenData      = $request->getAttribute('token_data');
        $securityStream = $request->getAttribute('security_stream');

        // Set account
        $account = array_merge(
            $account,
            [
                'company_id'    => $tokenData['company_id'] ?? 0,
                'company_title' => $tokenData['company_title'] ?? '',
                'roles'         => $roles,
            ]
        );

        // Do refresh
        $result = $this->accountService->refreshToken($account, $tokenId, ['security_stream' => $securityStream]);

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
