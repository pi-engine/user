<?php

namespace User\Repository;

use InvalidArgumentException;
use Laminas\Authentication\Adapter\DbTable\CallbackCheckAdapter;
use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Update;
use Laminas\Hydrator\HydratorInterface;
use RuntimeException;
use User\Model\Account\Account;
use User\Model\Account\Credential;

class AccountRepository implements AccountRepositoryInterface
{
    /**
     * Account Table name
     *
     * @var string
     */
    private string $tableAccount = 'user_account';

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $db;

    /**
     * @var HydratorInterface
     */
    private HydratorInterface $hydrator;

    /**
     * @var Account
     */
    private Account $accountPrototype;

    /**
     * @var Credential
     */
    private Credential $credentialPrototype;

    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        Account $accountPrototype,
        Credential $credentialPrototype
    ) {
        $this->db                  = $db;
        $this->hydrator            = $hydrator;
        $this->accountPrototype    = $accountPrototype;
        $this->credentialPrototype = $credentialPrototype;
    }

    public function getAccountList($params = []): HydratingResultSet
    {
        $where = [];

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableAccount)->where($where)->order($params['order'])->offset($params['offset'])->limit($params['limit']);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->accountPrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    public function getAccountCount($params = []): int
    {
        // Set where
        $columns = ['count' => new Expression('count(*)')];
        $where   = [];

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableAccount)->columns($columns)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $row       = $statement->execute()->current();

        return (int)$row['count'];
    }

    public function getAccountCredential(int $userId): string
    {
        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableAccount)->where(['id' => $userId]);
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

        $resultSet = new HydratingResultSet($this->hydrator, $this->credentialPrototype);
        $resultSet->initialize($result);
        $account = $resultSet->current();

        if (!$account) {
            throw new InvalidArgumentException(
                sprintf(
                    'Account with identifier "%s" not found.',
                    $userId
                )
            );
        }

        return $account->getCredential();
    }

    public function addAccount(array $params = []): Account
    {
        $insert = new Insert($this->tableAccount);
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

        return $this->getAccount(['id' => $id]);
    }

    public function getAccount(array $params = []): Account
    {
        // Set
        $where = [];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id'] = (int)$params['id'];
        } elseif (isset($params['identity']) && !empty($params['identity'])) {
            $where['identity'] = $params['identity'];
        } elseif (isset($params['email']) && !empty($params['email'])) {
            $where['email'] = $params['email'];
        }
        if (isset($params['status']) && (int)$params['status'] > 0) {
            $where['status'] = (int)$params['status'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableAccount)->where($where);
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

        $resultSet = new HydratingResultSet($this->hydrator, $this->accountPrototype);
        $resultSet->initialize($result);
        $account = $resultSet->current();

        if (!$account) {
            throw new InvalidArgumentException(
                sprintf(
                    'Account with identifier "%s" not found.',
                    $params
                )
            );
        }

        return $account;
    }

    public function updateAccount(int $userId, array $params = []): void
    {
        $update = new Update($this->tableAccount);
        $update->set($params);
        $update->where(['id' => $userId]);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($update);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }
    }

    public function count(array $params = []): int
    {
        // Set where
        $columns = ['count' => new Expression('count(*)')];
        $where   = [$params['field'] => $params['value']];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id <> ?'] = $params['id'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableAccount)->columns($columns)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $row       = $statement->execute()->current();

        return (int)$row['count'];
    }

    public function authentication(): AuthenticationService
    {
        // Call authAdapter
        $authAdapter = new CallbackCheckAdapter(
            $this->db,
            $this->tableAccount,
            'identity',
            'credential',
            function ($hash, $password) {
                $bcrypt = new Bcrypt();
                return $bcrypt->verify($password, $hash);
            }
        );

        // Set condition
        $select = $authAdapter->getDbSelect();
        $select->where(['status' => 1]);

        return new AuthenticationService(null, $authAdapter);
    }
}