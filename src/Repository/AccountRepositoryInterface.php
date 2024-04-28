<?php

namespace User\Repository;

use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Db\ResultSet\HydratingResultSet;
use User\Model\Account\Account;

interface AccountRepositoryInterface
{
    public function getAccountList($params = []): HydratingResultSet;

    public function getAccountCount($params = []): int;

    public function getAccount(array $params = []): array|Account;

    public function getAccountPassword(int $userId): string|null;

    public function addAccount(array $params = []): Account;

    public function updateAccount(int $userId, array $params = []): void;

    public function duplicatedAccount(array $params = []): int;

    public function addProfile(array $params = []): array|object;

    public function getProfile(array $params = []): array|object;

    public function updateProfile(int $userId, array $params = []): void;

    public function authentication($identityColumn = 'identity', $credentialColumn = 'credential', $hashPattern = 'bcrypt'): AuthenticationService;

    public function authenticationOauth($params): AuthenticationResult;
    public function authenticationOauth2($params): AuthenticationResult;

    public function getIdFromFilter(array $filter = []): HydratingResultSet|array;

    public function getMultiFactor(int $userId): array|null;

    public function getAccountProfileList($params = []): HydratingResultSet;

    public function getAccountProfile($params = []): array|object;
}