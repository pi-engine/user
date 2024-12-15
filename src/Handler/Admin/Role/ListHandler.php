<?php

namespace Pi\User\Handler\Admin\Role;

use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\RoleService;
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

    /** @var RoleService */
    protected RoleService $roleService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        RoleService              $roleService,
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->roleService     = $roleService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBody = $request->getParsedBody();

        // Reset cache
        $this->roleService->resetRoleListInCache();

        // Get role list
        $list = $this->roleService->getRoleResourceList($requestBody);

        $result
            = [
            'result' => true,
            'data'   => [
                'list' => $list,
            ],
            'error'  => new \stdClass(),
        ];
        return new EscapingJsonResponse($result);
    }
}
