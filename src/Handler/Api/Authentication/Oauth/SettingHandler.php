<?php

namespace User\Handler\Api\Authentication\Oauth;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Service\AccountService;

class SettingHandler implements RequestHandlerInterface
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
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data['oauth2']                  = $this->config['oauth2'];
        $data['oauth2']['authorize_url'] = sprintf(
            $this->config['oauth2']['authorize_url'],
            $this->config['oauth2']['client_id'],
            $this->config['oauth2']['response_type'],
            $this->config['oauth2']['scope'],
            $this->config['oauth2']['redirect_uri'],
            $this->config['oauth2']['state'],
            $this->config['oauth2']['nonce'],
            $this->config['oauth2']['response_mode'],
        );
        $result                          = [
            'result' => true,
            'data'   => $data,
            'error'  => [],
        ];

        return new JsonResponse($result, StatusCodeInterface::STATUS_OK);
    }
}