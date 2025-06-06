<?php

declare(strict_types=1);

namespace Pi\User\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\Sql\Delete;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Update;
use Laminas\Hydrator\HydratorInterface;
use Pi\Core\Repository\SignatureRepository;
use Pi\User\Model\Role\Account;
use Pi\User\Model\Role\Resource;
use RuntimeException;

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
     * @var SignatureRepository
     */
    protected SignatureRepository $signatureRepository;

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
        AdapterInterface    $db,
        SignatureRepository $signatureRepository,
        HydratorInterface   $hydrator,
        Resource            $rolePrototype,
        Account             $accountPrototype
    ) {
        $this->db                  = $db;
        $this->signatureRepository = $signatureRepository;
        $this->hydrator            = $hydrator;
        $this->rolePrototype       = $rolePrototype;
        $this->accountPrototype    = $accountPrototype;
    }

    public function getRoleResourceList($params = []): HydratingResultSet
    {
        $where = [];
        if (isset($params['section']) && !empty($params['section'])) {
            $where['section'] = $params['section'];
        }
        if (isset($params['status'])) {
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

    public function getRoleResource(array $params = []): array|Resource
    {
        // Set
        $where = [];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id'] = (int)$params['id'];
        } elseif (isset($params['name']) && !empty($params['name'])) {
            $where['name'] = $params['name'];
        } else {
            return [];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableRoleResource)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            throw new RuntimeException(
                'Failed retrieving row with identifier; unknown database error.',
            );
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->rolePrototype);
        $resultSet->initialize($result);
        $role = $resultSet->current();

        if (!$role) {
            return [];
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

        $id = $result->getGeneratedValue();

        // Generate and update new signature for the account
        $this->signatureRepository->updateSignature($this->tableRoleAccount, ['id' => $id]);
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
        $where = ['user_id' => $userId];
        if (in_array($section, ['api', 'admin'])) {
            $where['section'] = $section;
        }

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

    public function duplicatedRole(array $params = []): int
    {
        // Set where
        $columns = ['count' => new Expression('count(*)')];
        $where   = [$params['field'] => $params['value']];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id <> ?'] = $params['id'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableRoleResource)->columns($columns)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $row       = $statement->execute()->current();

        return (int)$row['count'];
    }
}