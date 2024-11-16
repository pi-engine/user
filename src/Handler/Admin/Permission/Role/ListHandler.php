<?php

namespace Pi\User\Handler\Admin\Permission\Role;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Pi\User\Service\PermissionService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListHandler implements RequestHandlerInterface
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
        $result      = $this->permissionService->getRoleList($requestBody);

        $result = [
            'result' => true,
            'data'   => $result,
            'error'  => new \stdClass(),
        ];

        return new JsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}