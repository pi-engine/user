<?php

namespace User\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\Sql\Delete;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Update;
use Laminas\Hydrator\HydratorInterface;
use User\Model\Role;
use User\Model\RoleAccount;
use RuntimeException;
use InvalidArgumentException;

class RoleRepository implements RoleRepositoryInterface
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
     * @var Role
     */
    private Role $rolePrototype;

    /**
     * @var RoleAccount
     */
    private RoleAccount $roleAccountPrototype;

    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        Role $rolePrototype,
        RoleAccount $roleAccountPrototype
    ) {
        $this->db                   = $db;
        $this->hydrator             = $hydrator;
        $this->rolePrototype        = $rolePrototype;
        $this->roleAccountPrototype = $roleAccountPrototype;
    }

    public function getRoleList($params = []): HydratingResultSet
    {
        $where = [];
        if (isset($params['section']) && !empty($params['section'])) {
            $where['section'] = $params['section'];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $where['status'] = (int)$params['status'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select('role')->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->rolePrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    public function getUserRoleList($params = []): HydratingResultSet
    {
        $where = [];

        $sql       = new Sql($this->db);
        $select    = $sql->select('role_account')->where($where)->order($params['order'])->offset($params['offset'])->limit($params['limit']);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->roleAccountPrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    public function getRole(array $params = []): Role
    {
        // Set
        $where = [];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id'] = (int)$params['id'];
        } elseif (isset($params['name']) && !empty($params['name'])) {
            $where['name'] = $params['name'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select('role')->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            throw new RuntimeException(
                sprintf(
                    'Failed retrieving blog post with identifier "%s"; unknown database error.',
                    $params
                )
            );
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->rolePrototype);
        $resultSet->initialize($result);
        $role = $resultSet->current();

        if (!$role) {
            throw new InvalidArgumentException(
                sprintf(
                    'Role with identifier "%s" not found.',
                    $params
                )
            );
        }

        return $role;
    }

    public function getUserRole($userId, $section = ''): HydratingResultSet
    {
        // Set
        $where = ['user_id' => $userId];
        if (isset($section) && !empty($section)) {
            $where['section'] = $section;
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select('role_account')->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            throw new RuntimeException(
                sprintf(
                    'Failed retrieving blog post with identifier "%s"; unknown database error.',
                    $userId
                )
            );
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->roleAccountPrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    public function addRole(array $params = []): Role
    {
        $insert = new Insert('role');
        $insert->values($params);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during blog post insert operation'
            );
        }

        $id = $result->getGeneratedValue();

        return $this->getRole(['id' => $id]);
    }

    public function addUserRole(array $params = []): RoleAccount
    {
        $insert = new Insert('role_account');
        $insert->values($params);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during blog post insert operation'
            );
        }

        $id = $result->getGeneratedValue();

        return $this->getUserRole(['id' => $id]);
    }

    public function updateRole(string $roleName, array $params = []): void
    {
        $update = new Update('role');
        $update->set($params);
        $update->where(['name' => $roleName]);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($update);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }
    }

    public function deleteUserRole(int $userId, string $roleName): void
    {
        $delete = new Delete('role_account');
        $delete->where(['user_id' => $userId, 'role' => $roleName]);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($delete);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }
    }
}