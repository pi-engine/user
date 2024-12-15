<?php

namespace Pi\User\Handler\Admin\Profile;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\Media\Service\MediaService;
use Pi\User\Service\AccountService;
use Pi\User\Service\ExportService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExportHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var AccountService */
    protected AccountService $accountService;

    /** @var ExportService */
    protected ExportService $exportService;

    /** @var MediaService */
    protected MediaService $mediaService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        AccountService           $accountService,
        ExportService            $exportService,
        MediaService             $mediaService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->accountService  = $accountService;
        $this->exportService   = $exportService;
        $this->mediaService    = $mediaService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Get accounts
        $params  = ['page' => 1, 'limit' => 1000];
        $account = $this->accountService->getAccountList($params);
        $export  = $this->exportService->exportData($account);
        $result  = $this->mediaService->streamFile($export);
        return new EscapingJsonResponse($result, StatusCodeInterface::STATUS_OK);
    }
}