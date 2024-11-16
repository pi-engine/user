<?php

namespace Pi\User\Handler\Api\Avatar;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Pi\User\Service\AccountService;
use Pi\User\Service\AvatarService;

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

        return new JsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}