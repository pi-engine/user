<?php

namespace User\Security;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class InputSizeLimit implements SecurityInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'inputSizeLimit';

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->config          = $config;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $securityStream
     *
     * @return array
     */
    public function check(ServerRequestInterface $request, array $securityStream = []): array
    {
        if ($this->isLargeInput($request)) {
            return [
                'result' => false,
                'name'   => $this->name,
                'status' => 'unsuccessful',
                'data'   => [],
            ];
        }

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [],
        ];
    }

    /**
     * Checks if the request input exceeds the maximum allowed size.
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function isLargeInput(ServerRequestInterface $request): bool
    {
        $body = $request->getBody();
        $size = $body->getSize();
        return $size > $this->config['inputSizeLimit']['max_input_size'];
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Access denied: Input data is too large';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }
}