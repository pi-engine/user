<?php

declare(strict_types=1);

namespace Pi\User\Repository;

use InvalidArgumentException;
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
use Pi\User\Model\Permission\Page;
use Pi\User\Model\Permission\Resource;
use Pi\User\Model\Permission\Role;
use RuntimeException;

class PermissionRepository implements PermissionRepositoryInterface
{
    /**
     * Permission resource Table name
     *
     * @var string
     */
    private string $tablePermissionResource = 'permission_resource';

    /**
     * Permission page Table name
     *
     * @var string
     */
    private string $tablePermissionPage = 'permission_page';

    /**
     * Permission role Table name
     *
     * @var string
     */
    private string $tablePermissionRole = 'permission_role';

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
    private Resource $resourcePrototype;

    /**
     * @var Role
     */
    private Role $rolePrototype;

    /**
     * @var Page
     */
    private Page $pagePrototype;

    public function __construct(
        AdapterInterface    $db,
        SignatureRepository $signatureRepository,
        HydratorInterface   $hydrator,
        Resource            $resourcePrototype,
        Role                $rolePrototype,
        Page                $pagePrototype
    ) {
        $this->db                  = $db;
        $this->signatureRepository = $signatureRepository;
        $this->hydrator            = $hydrator;
        $this->resourcePrototype   = $resourcePrototype;
        $this->rolePrototype       = $rolePrototype;
        $this->pagePrototype       = $pagePrototype;
    }

    /**
     * @param array $params
     *
     * @return HydratingResultSet
     */
    public function getPermissionResourceList(array $params = []): HydratingResultSet
    {
        $where = [];
        if (isset($params['title']) && !empty($params['title'])) {
            $where['title like ?'] = '%' . $params['title'] . '%';
        }
        if (isset($params['key']) && !empty($params['key'])) {
            $where['key'] = $params['key'];
        }
        if (isset($params['section']) && !empty($params['section'])) {
            $where['section'] = $params['section'];
        }
        if (isset($params['module']) && !empty($params['module'])) {
            $where['module'] = $params['module'];
        }
        if (isset($params['type']) && !empty($params['type'])) {
            $where['type'] = $params['type'];
        }

        $sql    = new Sql($this->db);
        $select = $sql->select($this->tablePermissionResource)->where($where);
        if (isset($params['order']) && !empty($params['order'])) {
            $select->order($params['order']);
        }
        if (isset($params['offset']) && !empty($params['offset'])) {
            $select->offset($params['offset']);
        }
        if (isset($params['limit']) && !empty($params['limit'])) {
            $select->limit($params['limit']);
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->resourcePrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    /**
     * @param array $params
     *
     * @return Resource
     */
    public function addPermissionResource(array $params = []): Resource
    {
        $insert = new Insert($this->tablePermissionResource);
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

        return $this->getPermissionResource(['id' => $id]);
    }

    /**
     * @param array $params
     *
     * @return Resource
     */
    public function getPermissionResource(array $params = []): Resource
    {
        // Set
        $where = [];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id'] = (int)$params['id'];
        } elseif (isset($params['key']) && !empty($params['key'])) {
            $where['key'] = $params['key'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tablePermissionResource)->where($where);
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

        $resultSet = new HydratingResultSet($this->hydrator, $this->resourcePrototype);
        $resultSet->initialize($result);
        $resource = $resultSet->current();

        if (!$resource) {
            throw new InvalidArgumentException(
                sprintf(
                    'Role with identifier "%s" not found.',
                    $params
                )
            );
        }

        return $resource;
    }

    /**
     * @param string $resourceKey
     * @param array  $params
     */
    public function updatePermissionResource(string $resourceKey, array $params = []): void
    {
        $update = new Update($this->tablePermissionResource);
        $update->set($params);
        $update->where(['key' => $resourceKey]);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($update);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }
    }

    /**
     * @param string $roleKey
     */
    public function deletePermissionResource(string $roleKey): void
    {
        // TODO: Implement deletePermissionResource() method.
    }

    public function getPermissionResourceCount($params = []): int
    {
        // Set columns
        $columns = ['count' => new Expression('count(*)')];

        $where = [];
        if (isset($params['title']) && !empty($params['title'])) {
            $where['title like ?'] = '%' . $params['title'] . '%';
        }
        if (isset($params['key']) && !empty($params['key'])) {
            $where['key'] = $params['key'];
        }
        if (isset($params['section']) && !empty($params['section'])) {
            $where['section'] = $params['section'];
        }
        if (isset($params['module']) && !empty($params['module'])) {
            $where['module'] = $params['module'];
        }
        if (isset($params['type']) && !empty($params['type'])) {
            $where['type'] = $params['type'];
        }

        // Get count
        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tablePermissionResource)->columns($columns)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $row       = $statement->execute()->current();

        return (int)$row['count'];
    }

    /**
     * @param array $params
     *
     * @return HydratingResultSet
     */
    public function getPermissionRoleList(array $params = []): HydratingResultSet
    {
        $where = [];
        if (isset($params['key']) && !empty($params['key'])) {
            $where['key'] = $params['key'];
        }
        if (isset($params['resource']) && !empty($params['resource'])) {
            $where['resource'] = $params['resource'];
        }
        if (isset($params['section']) && !empty($params['section'])) {
            $where['section'] = $params['section'];
        }
        if (isset($params['module']) && !empty($params['module'])) {
            $where['module'] = $params['module'];
        }
        if (isset($params['role']) && !empty($params['role'])) {
            $where['role'] = $params['role'];
        }

        $sql    = new Sql($this->db);
        $select = $sql->select($this->tablePermissionRole)->where($where);
        if (isset($params['order']) && !empty($params['order'])) {
            $select->order($params['order']);
        }
        if (isset($params['offset']) && !empty($params['offset'])) {
            $select->offset($params['offset']);
        }
        if (isset($params['limit']) && !empty($params['limit'])) {
            $select->limit($params['limit']);
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->rolePrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    /**
     * @param array $params
     *
     * @return Role
     */
    public function addPermissionRole(array $params = []): Role
    {
        $insert = new Insert($this->tablePermissionRole);
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

        // Generate and update new signature for the account
        //$this->signatureRepository->updateSignature($this->tablePermissionRole, ['id' => $id]);

        return $this->getPermissionRole(['id' => $id]);
    }

    /**
     * @param array $params
     *
     * @return Role
     */
    public function getPermissionRole(array $params = []): Role
    {
        // Set
        $where = [];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id'] = (int)$params['id'];
        } elseif (isset($params['key']) && !empty($params['key'])) {
            $where['key'] = $params['key'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tablePermissionRole)->where($where);
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

    /**
     * @param string $roleKey
     * @param array  $params
     */
    public function updatePermissionRole(string $roleKey, array $params = []): void
    {
        $update = new Update($this->tablePermissionRole);
        $update->set($params);
        $update->where(['key' => $roleKey]);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($update);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }

        // Generate and update new signature for the account
        $this->signatureRepository->updateSignature($this->tablePermissionRole, ['key' => $roleKey]);
    }

    /**
     * @param array $params
     */
    public function deletePermissionRole(array $params = []): void
    {
        // Delete from role table
        $delete = new Delete($this->tablePermissionRole);
        $delete->where($params);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($delete);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }
    }

    public function getPermissionRoleCount($params = []): int
    {
        // Set columns
        $columns = ['count' => new Expression('count(*)')];

        $where = [];
        if (isset($params['key']) && !empty($params['key'])) {
            $where['key'] = $params['key'];
        }
        if (isset($params['resource']) && !empty($params['resource'])) {
            $where['resource'] = $params['resource'];
        }
        if (isset($params['section']) && !empty($params['section'])) {
            $where['section'] = $params['section'];
        }
        if (isset($params['module']) && !empty($params['module'])) {
            $where['module'] = $params['module'];
        }
        if (isset($params['role']) && !empty($params['role'])) {
            $where['role'] = $params['role'];
        }

        // Get count
        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tablePermissionRole)->columns($columns)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $row       = $statement->execute()->current();

        return (int)$row['count'];
    }

    /**
     * @param array $params
     *
     * @return HydratingResultSet
     */
    public function getPermissionPageList(array $params = []): HydratingResultSet
    {
        $where = [];
        if (isset($params['title']) && !empty($params['title'])) {
            $where['title like ?'] = '%' . $params['title'] . '%';
        }
        if (isset($params['key']) && !empty($params['key'])) {
            $where['key'] = $params['key'];
        }
        if (isset($params['resource']) && !empty($params['resource'])) {
            $where['resource'] = $params['resource'];
        }
        if (isset($params['section']) && !empty($params['section'])) {
            $where['section'] = $params['section'];
        }
        if (isset($params['module']) && !empty($params['module'])) {
            $where['module'] = $params['module'];
        }
        if (isset($params['package']) && !empty($params['package'])) {
            $where['package'] = $params['package'];
        }
        if (isset($params['handler']) && !empty($params['handler'])) {
            $where['handler'] = $params['handler'];
        }

        $sql    = new Sql($this->db);
        $select = $sql->select($this->tablePermissionPage)->where($where);
        if (isset($params['order']) && !empty($params['order'])) {
            $select->order($params['order']);
        }
        if (isset($params['offset']) && !empty($params['offset'])) {
            $select->offset($params['offset']);
        }
        if (isset($params['limit']) && !empty($params['limit'])) {
            $select->limit($params['limit']);
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->pagePrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    /**
     * @param array $params
     *
     * @return Page
     */
    public function addPermissionPage(array $params = []): Page
    {
        $insert = new Insert($this->tablePermissionPage);
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

        return $this->getPermissionPage(['id' => $id]);
    }

    /**
     * @param array $params
     *
     * @return Page
     */
    public function getPermissionPage(array $params = []): Page
    {
        // Set
        $where = [];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id'] = (int)$params['id'];
        } elseif (isset($params['key']) && !empty($params['key'])) {
            $where['key'] = $params['key'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tablePermissionPage)->where($where);
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

        $resultSet = new HydratingResultSet($this->hydrator, $this->pagePrototype);
        $resultSet->initialize($result);
        $page = $resultSet->current();

        if (!$page) {
            throw new InvalidArgumentException(
                sprintf(
                    'Role with identifier "%s" not found.',
                    $params['key']
                )
            );
        }

        return $page;
    }

    /**
     * @param string $pageKey
     * @param array  $params
     */
    public function updatePermissionPage(string $pageKey, array $params = []): void
    {
        $update = new Update($this->tablePermissionPage);
        $update->set($params);
        $update->where(['key' => $pageKey]);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($update);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }
    }

    /**
     * @param array $params
     */
    public function deletePermissionPage(array $params = []): void
    {
        // TODO: Implement deletePermissionPage() method.
    }

    public function getPermissionPageCount($params = []): int
    {
        // Set columns
        $columns = ['count' => new Expression('count(*)')];

        $where = [];
        if (isset($params['title']) && !empty($params['title'])) {
            $where['title like ?'] = '%' . $params['title'] . '%';
        }
        if (isset($params['key']) && !empty($params['key'])) {
            $where['key'] = $params['key'];
        }
        if (isset($params['resource']) && !empty($params['resource'])) {
            $where['resource'] = $params['resource'];
        }
        if (isset($params['section']) && !empty($params['section'])) {
            $where['section'] = $params['section'];
        }
        if (isset($params['module']) && !empty($params['module'])) {
            $where['module'] = $params['module'];
        }
        if (isset($params['package']) && !empty($params['package'])) {
            $where['package'] = $params['package'];
        }
        if (isset($params['handler']) && !empty($params['handler'])) {
            $where['handler'] = $params['handler'];
        }

        // Get count
        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tablePermissionPage)->columns($columns)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $row       = $statement->execute()->current();

        return (int)$row['count'];
    }
}