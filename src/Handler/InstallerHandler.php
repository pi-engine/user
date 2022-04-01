<?php

namespace User\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Service\InstallerService;

class InstallerHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var InstallerService */
    protected InstallerService $installerService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        InstallerService $installerService
    ) {
        $this->responseFactory  = $responseFactory;
        $this->streamFactory    = $streamFactory;
        $this->installerService = $installerService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $permissionFile = include realpath(__DIR__ . '/../../config/module.permission.php');

        $this->installerService->installPermission('user', $permissionFile);

        // Set result
        return new JsonResponse(
            [
                'result' => true,
                'data'   => [],
                'error'  => [],
            ],
        );
    }
}
