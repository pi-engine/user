<?php

declare(strict_types=1);

namespace Pi\User\Handler\Api\Authentication\Mfa;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\MultiFactorService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RobThree\Auth\TwoFactorAuthException;

class RequestHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var MultiFactorService */
    protected MultiFactorService $multiFactorService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        MultiFactorService           $multiFactorService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->multiFactorService  = $multiFactorService;
    }

    /**
     * @throws TwoFactorAuthException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $account     = $request->getAttribute('account');
        $tokenId     = $request->getAttribute('token_id');
        $requestBody = $request->getParsedBody();

        // Do log in
        $result = $this->multiFactorService->requestMfa($account, $requestBody, $tokenId);

        return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}