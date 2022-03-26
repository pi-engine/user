<?php

namespace User\Service;

class InstallerService implements ServiceInterface
{
    /** @var PermissionService */
    protected PermissionService $permissionService;

    public function __construct() {}

    public function installPermission($permissionConfig)
    {
        $permissionConfig = $this->canonizePermission($permissionConfig);


        echo '<pre>';
        print_r($permissionConfig);
        echo '</pre>';
    }

    public function canonizePermission($permissionConfig)
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