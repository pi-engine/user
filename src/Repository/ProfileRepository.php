<?php

declare(strict_types=1);

namespace Pi\User\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\HydratorInterface;
use Pi\User\Model\Account\Profile;

class ProfileRepository implements AccountRoleRepositoryInterface
{
    /**
     * Profile Table name
     *
     * @var string
     */
    private string $tableProfile = 'user_profile';

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
        AdapterInterface  $db,
        HydratorInterface $hydrator,
        Profile           $profilePrototype
    ) {
        $this->db               = $db;
        $this->hydrator         = $hydrator;
        $this->profilePrototype = $profilePrototype;
    }
}