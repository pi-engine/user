<?php

declare(strict_types=1);

namespace Pi\User\Handler\Api\Avatar;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\AccountService;
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

    /** @var AccountService */
    protected AccountService $accountService;

    /** @var AvatarService */
    protected AvatarService $avatarService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        AccountService           $accountService,
        AvatarService            $avatarService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->avatarService   = $avatarService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uploadFiles = $request->getUploadedFiles();
        $account     = $request->getAttribute('account');
        $avatar      = $this->avatarService->uploadAvatar(array_shift($uploadFiles), $account);
        $profile     = $this->accountService->updateAccount($avatar, $account);

        // Set result array
        $result = [
            'result' => true,
            'data'   => $profile,
            'error'  => [],
        ];

        return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}