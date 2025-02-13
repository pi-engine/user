<?php

declare(strict_types=1);

namespace Pi\User\Handler\Admin\Permission\Page;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\PermissionService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;

class DeleteHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var PermissionService */
    protected PermissionService $permissionService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        PermissionService        $permissionService
    ) {
        $this->responseFactory   = $responseFactory;
        $this->streamFactory     = $streamFactory;
        $this->permissionService = $permissionService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBody = $request->getParsedBody();
        $result      = $this->permissionService->deletePermissionPage($requestBody);

        $result = [
            'result' => true,
            'data'   => $result,
            'error'  => new stdClass(),
        ];

        return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}