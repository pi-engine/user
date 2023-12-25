<?php

namespace User\Handler\Api\Authentication\Oauth;

use Fig\Http\Message\StatusCodeInterface;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Authentication\Oauth\Microsoft;
use User\Service\AccountService;

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
        StreamFactoryInterface $streamFactory,
        AccountService $accountService,
        $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->config              = $config;
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

        // Set params
        $params = ['token' => ['access_token' => $requestBody['accessToken']]];

        // Check
        $authService = new Microsoft($this->config);
        $userData    = $authService->verifyToken($params);

        // Do log in
        $result = $this->accountService->loginOauth($userData);

        return new JsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}