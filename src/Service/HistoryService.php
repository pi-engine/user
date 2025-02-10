<?php

declare(strict_types=1);

namespace Pi\User\Service;

use Pi\Core\Service\UtilityService;
use Pi\Logger\Service\LoggerService;

class HistoryService implements ServiceInterface
{
    /* @var LoggerService */
    protected LoggerService $loggerService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /* @var array */
    protected array $config;

    protected array $forbiddenParams
        = [
            'credential',
            'credentialColumn',
            'access_token',
            'refresh_token',
            'birthdate',
            'gender',
            'avatar',
            'information',
            'roles',
            'roles_full',
            'has_password',
            'multi_factor_global',
            'multi_factor_status',
            'multi_factor_method',
            'multi_factor_verify',
            'is_company_setup',
            'token_payload',
            'permission',
        ];

    public function __construct(
        LoggerService  $loggerService,
        UtilityService $utilityService,
                       $config
    ) {
        $this->loggerService  = $loggerService;
        $this->utilityService = $utilityService;
        $this->config         = $config;
    }

    public function logger(string $state, array $params): void
    {
        // Set user ip
        $params['ip']     = $this->utilityService->getClientIp();
        $params['method'] = $_SERVER['REQUEST_METHOD'];

        // Clean up
        $params = $this->cleanupForbiddenKeys($params);

        // Save log
        $this->loggerService->addUserLog($state, $params);
    }

    public function getUserLog($account, $params): array
    {
        $params = [
            'user_id' => $account['id'],
            'limit'   => $params['limit'] ?? 25,
            'page'    => $params['page'] ?? 1,
        ];

        return $this->loggerService->getUserLog($params);
    }

    public function cleanupForbiddenKeys(array $params): array
    {
        foreach ($params as $key => $value) {
            if (in_array($key, $this->forbiddenParams)) {
                unset($params[$key]);
            } elseif (is_array($value)) {
                $params[$key] = $this->cleanupForbiddenKeys($value);
            }
        }

        return $params;
    }
}