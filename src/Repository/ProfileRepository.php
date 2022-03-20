<?php

namespace User\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\HydratorInterface;
use User\Model\Profile\Profile;

class ProfileRepository implements AccountRoleRepositoryInterface
{
    /**
     * Profile Table name
     *
     * @var string
     */
    private string $tableProfile = 'profile';

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $db;

    /**
     * @var HydratorInterface
     */
    private HydratorInterface $hydrator;

    /**
     * @var Profile
     */
    private Profile $profilePrototype;

    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        Profile $profilePrototype
    ) {
        $this->db               = $db;
        $this->hydrator         = $hydrator;
        $this->profilePrototype = $profilePrototype;
    }
}