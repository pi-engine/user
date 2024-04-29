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
use User\Authentication\Oauth\Oauth2;
use User\Service\AccountService;

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
    )
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->accountService = $accountService;
        $this->config = $config;
    }

    /**
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Retrieve the raw JSON data from the request body
//        $stream = $this->streamFactory->createStreamFromFile('php://input');
//        $rawData = $stream->getContents();
//        $requestBody = json_decode($rawData, true);
//
//        // Check if decoding was successful
//        if (json_last_error() !== JSON_ERROR_NONE) {
//            // JSON decoding failed
//            $errorResponse = [
//                'result' => false,
//                'data' => null,
//                'error' => [
//                    'message' => 'Invalid JSON data',
//                ],
//            ];
//            return new JsonResponse($errorResponse, StatusCodeInterface::STATUS_UNAUTHORIZED);
//        }
        $requestBody = $request->getParsedBody();

        if (!isset($requestBody['code'])) {
            $errorResponse = [
                'result' => false,
                'data' => null,
                'error' => [
                    'message' => 'Invalid authentication data. please try again!',
                ],
            ];
            return new JsonResponse($errorResponse, StatusCodeInterface::STATUS_UNAUTHORIZED);
        }


        // Check
        $authService = new Oauth2($this->config);
        $result = $authService->verifyToken($requestBody);

        if (!$result['result']) {
            return new JsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
        }

        // Do log in
        $result = $this->accountService->loginOauth2($result['data']);

        return new JsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}