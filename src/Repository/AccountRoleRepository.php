<?php

namespace User\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\HydratorInterface;
use User\Model\AccountRole;

class AccountRoleRepository implements AccountRoleRepositoryInterface
{
    /**
     * @var AdapterInterface
     */
    private AdapterInterface $db;

    /**
     * @var HydratorInterface
     */
    private HydratorInterface $hydrator;

    /**
     * @var AccountRole
     */
    private AccountRole $accountRolePrototype;

    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        AccountRole $accountRolePrototype
    ) {
        $this->db                   = $db;
        $this->hydrator             = $hydrator;
        $this->accountRolePrototype = $accountRolePrototype;
    }
}