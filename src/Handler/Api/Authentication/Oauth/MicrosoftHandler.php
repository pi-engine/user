<?php

declare(strict_types=1);

namespace Pi\User\Handler\Api\Authentication\Oauth;

use Fig\Http\Message\StatusCodeInterface;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Laminas\Http\Header\SetCookie;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Authentication\Oauth\Microsoft;
use Pi\User\Service\AccountService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MicrosoftHandler implements RequestHandlerInterface
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
            'token'           => [
                'access_token' => $requestBody['access_token'],
            ],
            'security_stream' => $securityStream,
        ];

        // Check
        $authService = new Microsoft($this->config);
        $userData    = $authService->verifyToken($params);

        // Do log in
        $result = $this->accountService->loginOauth(array_merge($userData, ['security_stream' => $securityStream]));

        // Make a escaping json response
        $response = new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);


        // Set httponly cookie
        if (isset($result['data']['access_token']) && !empty($result['data']['access_token'])) {
            $cookie   = new SetCookie('access_token', $result['data']['access_token'], $result['data']['token_payload']['exp'], '/', null, true, true);
            $response = $response->withHeader('Set-Cookie', $cookie->getFieldValue());
        }

        return $response;
    }
}