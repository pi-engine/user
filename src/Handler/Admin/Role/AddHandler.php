<?php

namespace User\Handler\Admin\Role;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;
use User\Service\RoleService;

class AddHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var RoleService */
    protected RoleService $roleService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        RoleService $roleService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->roleService     = $roleService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBody = $request->getParsedBody();
        $operator    = $request->getAttribute('account');

        // Reset cache
        $this->roleService->resetRoleListInCache();

        // Get role list
        $list = $this->roleService->getRoleResourceListByAdmin();

        // Check duplicate
        $isDuplicate = false;
        foreach ($list as $item) {
            if ($item["name"] === $requestBody['name'] ?? '') {
                $isDuplicate = true;
                break; // Exit the loop once a match is found
            }
        }

        if ($isDuplicate || !isset($requestBody['name']) || empty($requestBody['name'])) {
            return new JsonResponse(
                [
                    'result' => false,
                    'data'   => new stdClass(),
                    'error'  => [
                        'message' => 'This role added before!',
                        'code'    => 403,
                    ],
                ]
            );
        }

        // Add role
        $result = $this->roleService->addRoleResource($requestBody, $operator);

        return new JsonResponse(
            [
                'result' => true,
                'data'   => $result,
                'error'  => [],
            ]
        );
    }
}
