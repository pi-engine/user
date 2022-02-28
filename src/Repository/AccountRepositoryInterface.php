<?php

namespace User\Repository;

use Laminas\Authentication\AuthenticationService;
use User\Model\Account;

interface AccountRepositoryInterface
{
    public function getAccounts($params = []);

    public function getAccount(array $params = []): Account;

    public function addAccount(array $params = []): Account;

    public function count(array $params = []): int;

    public function authentication() : AuthenticationService;
}