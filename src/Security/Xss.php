<?php

namespace User\Security;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use voku\helper\AntiXSS;

class Xss implements SecurityInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public function check(ServerRequestInterface $request): bool
    {
        // Retrieve the raw JSON data from the request body
        $stream       = $this->streamFactory->createStreamFromFile('php://input');
        $streamParams = $stream->getContents();

        // Get request body
        $requestParams = $request->getParsedBody();

        // Call XSS checker
        $antiXss = new AntiXSS();

        // Check request
        if (!empty($requestParams)) {
            $antiXss->xss_clean($requestParams);
            if ($antiXss->isXssFound()) {
                return false;
            }
        }

        // Check stream
        if (!empty($streamParams)) {
            $antiXss->xss_clean($streamParams);
            if ($antiXss->isXssFound()) {
                return false;
            }
        }

        // old method
        /* foreach ($params as $param) {
            if (is_array($param)) {
                foreach ($param as $subParam) {
                    if ($subParam != htmlspecialchars($subParam, ENT_QUOTES, 'UTF-8')) {
                        return false;
                    }
                }
            } else {
                if ($param != htmlspecialchars($param, ENT_QUOTES, 'UTF-8')) {
                    return false;
                }
            }
        } */

        return true;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'XSS attack detected !';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }
}