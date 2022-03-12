<?php

namespace User\Repository;

use Laminas\Authentication\AuthenticationService;
use Laminas\Db\ResultSet\HydratingResultSet;
use User\Model\Account;

interface AccountRepositoryInterface
{
    public function getAccountList($params = []): HydratingResultSet;

    public function getAccountCount($params = []): int;

    public function getAccount(array $params = []): Account;

    public function getAccountCredential(int $userId): string;

    public function addAccount(array $params = []): Account;

    public function updateAccount(int $userId, array $params = []): void;

    public function count(array $params = []): int;

    public function authentication(): AuthenticationService;
}