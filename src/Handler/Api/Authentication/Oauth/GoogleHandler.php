<?php

namespace User\Handler\Api\Authentication\Oauth;

use Fig\Http\Message\StatusCodeInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Google\Auth\AccessToken;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Service\AccountService;

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
        StreamFactoryInterface $streamFactory,
        AccountService $accountService,
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
        // Retrieve the raw JSON data from the request body
        $stream      = $this->streamFactory->createStreamFromFile('php://input');
        $rawData     = $stream->getContents();
        $requestBody = json_decode($rawData, true);

        // Check if decoding was successful
        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON decoding failed
            $errorResponse = [
                'result' => false,
                'data'   => null,
                'error'  => [
                    'message' => 'Invalid JSON data',
                ],
            ];
            return new JsonResponse($errorResponse, StatusCodeInterface::STATUS_UNAUTHORIZED);
        }


        $secret_key = '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';
        $access_token = new AccessToken();
        $claims = $access_token->verify($requestBody['credential'], [
            'client_id' => $secret_key,
        ]);

        echo '<pre>';
        var_dump($claims);
        die;


        // Set params
        $params = ['token' => ['access_token' => $requestBody['credential']]];

        // Check
        /* $authService = new Google($this->config);
        $userData    = $authService->verifyToken($params);

        // Do log in
        $result = $this->accountService->loginOauth($userData); */

        return new JsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}