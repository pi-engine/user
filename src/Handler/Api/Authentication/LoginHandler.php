<?php

declare(strict_types=1);

namespace Pi\User\Handler\Api\Authentication;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Http\Header\SetCookie;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\AccountService;
use Pi\User\Service\TokenService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoginHandler implements RequestHandlerInterface
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
        $securityStream = $request->getAttribute('security_stream');
        $requestBody    = $request->getParsedBody();

        // Set identity
        if (isset($requestBody['email']) && !empty($requestBody['email'])) {
            $identity       = $requestBody['email'];
            $identityColumn = 'email';
        } elseif (isset($requestBody['mobile']) && !empty($requestBody['mobile'])) {
            $identity       = $requestBody['mobile'];
            $identityColumn = 'mobile';
        } else {
            $identity       = $requestBody['identity'];
            $identityColumn = 'identity';
        }

        // Set login params
        $params = [
            'identity'        => $identity,
            'identityColumn'  => $identityColumn,
            'credential'      => $requestBody['credential'],
            'source'          => $requestBody['source'] ?? '',
            'security_stream' => $securityStream,
        ];

        // Do log in
        $result = $this->accountService->login($params);

        // Make a escaping json response
        $response = new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);

        // Set httponly cookie
        if (isset($result['data']['access_token']) && !empty($result['data']['access_token'])) {
            $cookie   = new SetCookie('access_token', $result['data']['access_token'], $result['data']['token_payload']['exp'], '/', null, true, true);
            $response = $response->withAddedHeader('Set-Cookie', $cookie->toString());
        }

        return $response;
    }
}
