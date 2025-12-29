<?php

declare(strict_types=1);

namespace Pi\User\Handler\Api\Authentication\Oauth;

use Fig\Http\Message\StatusCodeInterface;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Authentication\Oauth\Google;
use Pi\User\Service\AccountService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GoogleHandler implements RequestHandlerInterface
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
     * @throws UnexpectedApiResponseException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $securityStream = $request->getAttribute('security_stream');
        $requestBody    = $request->getParsedBody();

        // Set params
        $params = [
            'credential'      => $requestBody['credential'],
            'security_stream' => $securityStream,
        ];

        // Check
        $authService = new Google($this->config);
        $userData    = $authService->verifyToken($params);

        // Do log in
        $result = $this->accountService->loginOauth(array_merge($userData, ['security_stream' => $securityStream]));

        // Make a escaping json response
        $response = new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);

        // Set httponly cookie for access token and refresh token
        $accessTokenCookie  = $this->accountService->accessTokenCookie($result);
        $refreshTokenCookie = $this->accountService->refreshTokenCookie($result);
        if (!empty($accessTokenCookie) && !empty($refreshTokenCookie)) {
            $response = $response
                ->withAddedHeader('Set-Cookie', $accessTokenCookie)
                ->withAddedHeader('Set-Cookie', $refreshTokenCookie);
        }

        return $response;
    }
}