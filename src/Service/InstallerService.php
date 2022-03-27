<?php

namespace User\Service;

class InstallerService implements ServiceInterface
{
    /** @var PermissionService */
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function installPermission($module, array $permissionConfig)
    {
        // Canonize
        $permissionConfig = $this->canonizePermission($permissionConfig);

        // Get list of exist pages and permission
        $installerList = $this->permissionService->getInstallerList(['module' => $module]);

        // inset
        foreach ($permissionConfig as $permissionList) {
            foreach ($permissionList as $permissionSingle) {
                echo '<pre>';
                print_r($permissionSingle);
                echo '</pre>';

                if (
                    !isset($installerList['page_list'][$permissionSingle['page']])
                    && !isset($installerList['resource_list'][$permissionSingle['permissions']])
                ) {
                    $resourceParams = array_merge(
                        $permissionSingle,
                        [
                            'name' => $permissionSingle['permissions'],
                        ]
                    );

                    $resource = $this->permissionService->addPermissionResource($resourceParams);


                    $pageParams = array_merge(
                        $permissionSingle,
                        [
                            'name'     => $permissionSingle['page'],
                            'resource' => $resource['name'],
                        ]
                    );

                    $this->permissionService->addPermissionPage($pageParams);

                    foreach ($permissionSingle['role'] as $role) {
                        $roleParams = array_merge(
                            $permissionSingle,
                            [
                                'name'     => sprintf('%s-%s', $role, $permissionSingle['permissions']),
                                'resource' => $resource['name'],
                                'role' => $role
                            ]
                        );

                        $this->permissionService->addPermissionRole($roleParams);
                    }
                }
            }
        }


        echo '<pre>';
        print_r($installerList);
        echo '</pre>';
    }

    public function canonizePermission(array $permissionConfig): array
    {
        foreach ($permissionConfig as $permissionSection => $permissionList) {
            foreach ($permissionList as $permissionSingleKey => $permissionSingle) {

                if (!isset($permissionSingle['page']) || empty($permissionSingle['page'])) {
                    $permissionSingle['page'] = sprintf(
                        '%s-%s-%s-%s',
                        $permissionSingle['section'],
                        $permissionSingle['module'],
                        $permissionSingle['package'],
                        $permissionSingle['handler']
                    );
                }

                if (!isset($permissionSingle['permissions']) || empty($permissionSingle['permissions'])) {
                    $permissionSingle['permissions'] = sprintf(
                        '%s-%s-%s-%s',
                        $permissionSingle['section'],
                        $permissionSingle['module'],
                        $permissionSingle['package'],
                        $permissionSingle['handler']
                    );
                }

                $permissionConfig[$permissionSection][$permissionSingleKey] = $permissionSingle;
            }
        }

        return $permissionConfig;
    }
}