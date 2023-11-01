<?php

namespace User\Service;

use Logger\Service\LoggerService;

class HistoryService implements ServiceInterface
{
    /* @var LoggerService */
    protected LoggerService $loggerService;

    /* @var array */
    protected array $config;

    protected array $forbiddenParams = ['credential', 'credentialColumn'];

    public function __construct(
        LoggerService $loggerService,
                      $config
    )
    {
        $this->loggerService = $loggerService;
        $this->config = $config;
    }

    public function logger(string $state, array $params): void
    {
        // Clean up
        foreach ($params as $key => $value) {
            if (in_array($key, $this->forbiddenParams)) {
                unset($params[$key]);
            }
        }
        ///TODO: improve this
        $params['params']['ip'] = $_SERVER['REMOTE_ADDR'];

        // Save log
        $this->loggerService->addUserLog($state, $params);
    }

    public function getUserLog($account): array
    {
        $params = [
            'user_id' => $account['id'],
            'limit' => 25,
            'page' => 1,
        ];

        return $this->loggerService->getUserLog($params);
    }
}