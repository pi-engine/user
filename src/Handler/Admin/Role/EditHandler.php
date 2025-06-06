<?php

declare(strict_types=1);

namespace Pi\User\Handler\Admin\Role;

use Pi\Core\Response\EscapingJsonResponse;
use Pi\User\Service\RoleService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;

class EditHandler implements RequestHandlerInterface
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
        RoleService              $roleService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->roleService     = $roleService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBody = $request->getParsedBody();
        $role        = $request->getAttribute('role_item');
        $operator    = $request->getAttribute('account');

        // Update
        $this->roleService->updateRoleResource($role, $requestBody, $operator);

        return new EscapingJsonResponse(
            [
                'result' => true,
                'data'   => new stdClass(),
                'error'  => [],
            ]
        );
    }
}
