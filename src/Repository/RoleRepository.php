<?php

namespace User\Repository;

use InvalidArgumentException;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\Sql\Delete;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Update;
use Laminas\Hydrator\HydratorInterface;
use RuntimeException;
use User\Model\Role\Account;
use User\Model\Role\Resource;

class RoleRepository implements RoleRepositoryInterface
{
    /**
     * Role Table name
     *
     * @var string
     */
    private string $tableRoleResource = 'role_resource';

    /**
     * Role Account Table name
     *
     * @var string
     */
    private string $tableRoleAccount = 'role_account';

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $db;

    /**
     * @var HydratorInterface
     */
    private HydratorInterface $hydrator;

    /**
     * @var Resource
     */
    private Resource $rolePrototype;

    /**
     * @var Account
     */
    private Account $accountPrototype;

    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        Resource $rolePrototype,
        Account $accountPrototype
    ) {
        $this->db               = $db;
        $this->hydrator         = $hydrator;
        $this->rolePrototype    = $rolePrototype;
        $this->accountPrototype = $accountPrototype;
    }

    public function getRoleResourceList($params = []): HydratingResultSet
    {
        $where = [];
        if (isset($params['section']) && !empty($params['section'])) {
            $where['section'] = $params['section'];
        }
        if (isset($params['status'])  ) {
            $where['status'] = (int)$params['status'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableRoleResource)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->rolePrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    public function addRoleResource(array $params = []): Resource
    {
        $insert = new Insert($this->tableRoleResource);
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

        return $this->getRoleResource(['id' => $id]);
    }

    public function getRoleResource(array $params = []): Resource
    {
        // Set
        $where = [];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id'] = (int)$params['id'];
        } elseif (isset($params['name']) && !empty($params['name'])) {
            $where['name'] = $params['name'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableRoleResource)->where($where);
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

    public function updateRoleResource(string $roleName, array $params = []): void
    {
        $update = new Update($this->tableRoleResource);
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

    public function deleteRoleResource(string $roleName): void
    {
        // Delete from role table
        $delete = new Delete($this->tableRoleResource);
        $delete->where(['role' => $roleName]);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($delete);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }

        // Delete from role_account table
        $delete = new Delete($this->tableRoleAccount);
        $delete->where(['role' => $roleName]);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($delete);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }
    }

    public function getRoleAccount($userId, $section = ''): HydratingResultSet
    {
        // Set
        $where = ['user_id' => $userId];
        if (isset($section) && !empty($section)) {
            $where['section'] = $section;
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableRoleAccount)->where($where);
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

        $resultSet = new HydratingResultSet($this->hydrator, $this->accountPrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    public function addRoleAccount(int $userId, string $roleName, string $section = 'api'): void
    {
        $value = [
            'user_id' => $userId,
            'role'    => $roleName,
            'section' => $section,
        ];

        $insert = new Insert($this->tableRoleAccount);
        $insert->values($value);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during blog post insert operation'
            );
        }
    }

    public function deleteRoleAccount(int $userId, string $roleName): void
    {
        $delete = new Delete($this->tableRoleAccount);
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
    public function deleteAllRoleAccount(int $userId, string $section = 'api'): void
    {
        $where = [
            'user_id' => $userId,
            'section' => $section
        ];

        $delete = new Delete($this->tableRoleAccount);
        $delete->where($where);

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