<?php

namespace User\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Handler\ErrorHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Service\PermissionService;
use User\Service\RoleService;

class AuthorizationMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var RoleService */
    protected RoleService $roleService;

    /** @var PermissionService */
    protected PermissionService $permissionService;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        RoleService              $roleService,
        PermissionService        $permissionService,
        ErrorHandler             $errorHandler
    ) {
        $this->responseFactory   = $responseFactory;
        $this->streamFactory     = $streamFactory;
        $this->roleService       = $roleService;
        $this->permissionService = $permissionService;
        $this->errorHandler      = $errorHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get route params
        $routeMatch  = $request->getAttribute('Laminas\Router\RouteMatch');
        $routeParams = $routeMatch->getParams();

        // Check and get system role list
        switch ($routeParams['section']) {
            case 'api':
                $roleList = $this->roleService->getApiRoleList();
                break;

            case 'admin':
                $roleList = $this->roleService->getAdminRoleList();
                break;

            default:
                $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
                $request = $request->withAttribute(
                    'error',
                    [
                        'message' => 'Section not set !',
                        'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                    ]
                );
                return $this->errorHandler->handle($request);
                break;
        }

        // Clean Up requested user roles
        $userRoles = $request->getAttribute('roles');
        $userRoles = array_combine($userRoles, $userRoles);
        foreach ($userRoles as $role) {
            if (!in_array($role, $roleList)) {
                unset($userRoles[$role]);
            }
        }

        // Check section
        if (empty($userRoles)) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => 'You dont have access to this area ! 1',
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ]
            );
            return $this->errorHandler->handle($request);
        }


        // Set page key
        $pageKey = sprintf(
            '%s-%s-%s-%s',
            $routeParams['section'],
            $routeParams['module'],
            $routeParams['package'],
            $routeParams['handler']
        );

        // Get and check access
        $access = $this->permissionService->checkPermissionBefore($pageKey, $userRoles);
        if (!$access) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => 'You dont have access to this area ! 2',
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Set attribute
        $request = $request->withAttribute('roles', array_values($userRoles));
        return $handler->handle($request);
    }
}