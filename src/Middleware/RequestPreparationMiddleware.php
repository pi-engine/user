<?php

namespace User\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Handler\ErrorHandler;

class RequestPreparationMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ErrorHandler $errorHandler
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->errorHandler    = $errorHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');

        // Check for JSON or form data content types
        if ($this->isJson($contentType)) {
            return $this->processJsonRequest($request, $handler);
        } elseif ($this->isFormData($contentType)) {
            // Handle form data, if necessary
            return $handler->handle($request);
        } else {
            // Unsupported content type
            return $this->createErrorResponse($request, 'Unsupported content type', StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }

    private function createErrorResponse(ServerRequestInterface $request, string $message): ResponseInterface
    {
        $request = $request->withAttribute('status', StatusCodeInterface::STATUS_BAD_REQUEST);
        $request = $request->withAttribute('error', ['message' => $message, 'code' => StatusCodeInterface::STATUS_BAD_REQUEST]);
        return $this->errorHandler->handle($request);
    }

    private function isJson(string $contentType): bool
    {
        return stripos($contentType, 'application/json') !== false;
    }

    private function isFormData(string $contentType): bool
    {
        return stripos($contentType, 'application/x-www-form-urlencoded') !== false ||
               stripos($contentType, 'multipart/form-data') !== false;
    }

    private function processJsonRequest(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $stream = $this->streamFactory->createStreamFromFile('php://input');
        $rawData = $stream->getContents();

        if (!empty($rawData)) {
            $parsedBody = json_decode($rawData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->createErrorResponse($request, 'Invalid JSON data', StatusCodeInterface::STATUS_BAD_REQUEST);
            }

            $request = $request->withParsedBody($parsedBody);
        }

        return $handler->handle($request);
    }
}