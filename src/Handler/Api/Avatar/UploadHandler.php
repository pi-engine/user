<?php

declare(strict_types=1);

namespace Pi\User\Handler\Api\Avatar;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\AvatarService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UploadHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var AvatarService */
    protected AvatarService $avatarService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        AvatarService            $avatarService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->avatarService   = $avatarService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uploadFiles = $request->getUploadedFiles();
        $account     = $request->getAttribute('account');
        $result      = $this->avatarService->uploadAvatar(array_shift($uploadFiles), $account);

        return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}