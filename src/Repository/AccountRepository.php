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
use User\Model\Account\Profile;
use function sprintf;
use function var_dump;

class AccountRepository implements AccountRepositoryInterface
{
    /**
     * Account Table name
     *
     * @var string
     */
    private string $tableAccount = 'user_account';

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
     * @var Account
     */
    private Account $accountPrototype;

    /**
     * @var Profile
     */
    private Profile $profilePrototype;

    /**
     * @var Credential
     */
    private Credential $credentialPrototype;

    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        Account $accountPrototype,
        Profile $profilePrototype,
        Credential $credentialPrototype
    ) {
        $this->db                  = $db;
        $this->hydrator            = $hydrator;
        $this->accountPrototype    = $accountPrototype;
        $this->profilePrototype    = $profilePrototype;
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

    public function getAccount(array $params = []): array|Account
    {
        // Set
        $where = [];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id'] = (int)$params['id'];
        } elseif (isset($params['identity']) && !empty($params['identity'])) {
            $where['identity'] = $params['identity'];
        } elseif (isset($params['email']) && !empty($params['email'])) {
            $where['email'] = $params['email'];
        } elseif (isset($params['mobile']) && !empty($params['mobile'])) {
            $where['mobile'] = $params['mobile'];
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
            return [];
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

    public function authentication($identityColumn = 'identity', $credentialColumn = 'credential'): AuthenticationService
    {
        // Call authAdapter
        $authAdapter = new CallbackCheckAdapter(
            $this->db,
            $this->tableAccount,
            $identityColumn,
            $credentialColumn,
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

    /**
     * @param array $params
     *
     * @return array
     */
    public function getProfile(array $params = []): array|object
    {
        // Set
        $where = [];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id'] = (int)$params['id'];
        } elseif (isset($params['user_id']) && (int)$params['user_id'] > 0) {
            $where['user_id'] = $params['user_id'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableProfile)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->profilePrototype);
        $resultSet->initialize($result);
        $profile = $resultSet->current();

        if (!$profile) {
            return [];
        }

        return $profile;
    }

    /**
     * @param array $params
     *
     * @return Profile
     */
    public function addProfile(array $params = []): array|object
    {
        $insert = new Insert($this->tableProfile);
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

        return $this->getProfile(['id' => $id]);
    }

    /**
     * @param array $params
     *
     * @return void
     */
    public function updateProfile(int $userId, array $params = []): void
    {
        $update = new Update($this->tableProfile);
        $update->set($params);
        $update->where(['user_id' => $userId]);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($update);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }
}
}