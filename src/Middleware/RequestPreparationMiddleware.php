<?php

namespace User\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Stream;
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

    /* @var array */
    protected array $config;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ErrorHandler $errorHandler,
        $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->errorHandler    = $errorHandler;
        $this->config          = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');

        // Check for JSON or form data content types
        if ($this->isJson($contentType)) {
            $response = $this->processJsonRequest($request, $handler);
        } elseif ($this->isFormData($contentType)) {
            // Handle form data, if necessary
            $response = $handler->handle($request);
        } else {
            // Todo: Fix it
            // Unsupported content type
            //return $this->createErrorResponse($request, 'Unsupported content type', StatusCodeInterface::STATUS_BAD_REQUEST);
            $response = $handler->handle($request);
        }

        // Check if the response can be compressed and compressed it
        if (isset($this->config['compress']['is_active']) && $this->config['compress']['is_active']) {
            if ($this->canCompress($request)) {
                $body = (string) $response->getBody();
                $compressedBody = gzencode($body, 9);

                // Create a new stream with the compressed body
                $stream = new Stream('php://temp', 'wb+');
                $stream->write($compressedBody);
                $stream->rewind();

                // Return the response with the compressed body
                return $response
                    ->withBody($stream)
                    ->withHeader('Content-Encoding', 'gzip')
                    ->withHeader('Content-Length', strlen($compressedBody));
            }
        }

        return $response;
    }

    private function createErrorResponse(ServerRequestInterface $request, string $message): ResponseInterface
    {
        $request = $request->withAttribute('status', StatusCodeInterface::STATUS_BAD_REQUEST);
        $request = $request->withAttribute('error', ['message' => $message, 'code' => StatusCodeInterface::STATUS_BAD_REQUEST]);
        return $this->errorHandler->handle($request);
    }

    private function isJson(string $contentType): bool
    {
        return stripos($contentType, 'application/json') !== false
               || stripos($contentType, 'text/plain') !== false;
    }

    private function isFormData(string $contentType): bool
    {
        return stripos($contentType, 'application/x-www-form-urlencoded') !== false
               || stripos($contentType, 'multipart/form-data') !== false;
    }

    private function canCompress(ServerRequestInterface $request): bool
    {
        $acceptEncoding = $request->getHeaderLine('Accept-Encoding');
        return str_contains($acceptEncoding, 'gzip');
    }

    private function processJsonRequest(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $stream  = $this->streamFactory->createStreamFromFile('php://input');
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