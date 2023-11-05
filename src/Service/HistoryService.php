<?php

namespace User\Service;

use Logger\Service\LoggerService;

class HistoryService implements ServiceInterface
{
    /* @var LoggerService */
    protected LoggerService $loggerService;

    /* @var array */
    protected array $config;

    protected array $forbiddenParams = ['credential', 'credentialColumn', 'access_token', 'refresh_token'];

    public function __construct(
        LoggerService $loggerService,
        $config
    ) {
        $this->loggerService = $loggerService;
        $this->config        = $config;
    }

    public function logger(string $state, array $params): void
    {
        // TODO: improve this
        $params['params']['serverParams'] = $_SERVER;

        // Clean up
        $params = $this->cleanupForbiddenKeys($params);

        // Save log
        $this->loggerService->addUserLog($state, $params);
    }

    public function getUserLog($account): array
    {
        $params = [
            'user_id' => $account['id'],
            'limit'   => 25,
            'page'    => 1,
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