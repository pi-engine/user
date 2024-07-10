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

class JsonToArrayMiddleware implements MiddlewareInterface
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
        $this->responseFactory    = $responseFactory;
        $this->streamFactory      = $streamFactory;
        $this->errorHandler       = $errorHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get json information from request
        $stream     = $this->streamFactory->createStreamFromFile('php://input');
        $rawData    = $stream->getContents();

        // Decode the raw JSON data into an associative array
        $parsedBody = json_decode($rawData, true);

        // Check if decoding was successful
        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON decoding failed
            $request      = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request      = $request->withAttribute(
                'error',
                [
                    'message' => 'Invalid JSON data !',
                    'code'    => StatusCodeInterface::STATUS_BAD_REQUEST,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Set attribute
        $request = $request->withParsedBody($parsedBody);
        return $handler->handle($request);
    }
}