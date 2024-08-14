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

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $name = 'xss';

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
        // Check if the IP is in the whitelist
        if (
            (bool)$this->config['xss']['ignore_whitelist'] === true
            && isset($securityStream['ip']['data']['in_whitelist'])
            && (bool)$securityStream['ip']['data']['in_whitelist'] === true
        ) {
            return [
                'result' => true,
                'name'   => $this->name,
                'status' => 'ignore',
                'data'   => [],
            ];
        }

        // Get request body
        $requestParams = $request->getParsedBody();

        // Call XSS checker
        $antiXss = new AntiXSS();

        // Check request
        if (!empty($requestParams)) {
            $antiXss->xss_clean($requestParams);
            if ($antiXss->isXssFound()) {
                return [
                    'result' => false,
                    'name'   => $this->name,
                    'status' => 'unsuccessful',
                    'data'   => [],
                ];
            }
        }

        return [
            'result' => true,
            'name'   => $this->name,
            'status' => 'successful',
            'data'   => [],
        ];
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Access denied: XSS attack detected';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }
}