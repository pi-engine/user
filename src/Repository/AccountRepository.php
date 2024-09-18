<?php

namespace User\Repository;

use InvalidArgumentException;
use Laminas\Authentication\Adapter\DbTable\CallbackCheckAdapter;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Predicate\IsNotNull;
use Laminas\Db\Sql\Predicate\NotIn;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Update;
use Laminas\Hydrator\HydratorInterface;
use RuntimeException;
use User\Authentication\Adapter\OauthAdapter;
use User\Model\Account\Account;
use User\Model\Account\AccountProfile;
use User\Model\Account\Credential;
use User\Model\Account\MultiFactor;
use User\Model\Account\Profile;
use User\Model\Role\Account as AccountRole;

use function sprintf;

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
     * @var Account
     */
    private Account $accountPrototype;

    /**
     * @var AccountProfile
     */
    private AccountProfile $accountProfilePrototype;

    /**
     * @var Profile
     */
    private Profile $profilePrototype;
    /**
     * @var AccountRole
     */
    private AccountRole $accountRolePrototype;

    /**
     * @var Credential
     */
    private Credential $credentialPrototype;

    /**
     * @var MultiFactor
     */
    private MultiFactor $multiFactorPrototype;

    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        Account $accountPrototype,
        AccountProfile $accountProfilePrototype,
        Profile $profilePrototype,
        AccountRole $accountRolePrototype,
        Credential $credentialPrototype,
        MultiFactor $multiFactorPrototype
    ) {
        $this->db                      = $db;
        $this->hydrator                = $hydrator;
        $this->accountPrototype        = $accountPrototype;
        $this->accountProfilePrototype = $accountProfilePrototype;
        $this->profilePrototype        = $profilePrototype;
        $this->accountRolePrototype    = $accountRolePrototype;
        $this->credentialPrototype     = $credentialPrototype;
        $this->multiFactorPrototype    = $multiFactorPrototype;
    }

    public function getAccountList($params = []): HydratingResultSet
    {
        // Add filter
        $where['time_deleted'] = 0;
        if (isset($params['name']) && !empty($params['name'])) {
            $where['name like ?'] = '%' . $params['name'] . '%';
        }
        if (isset($params['identity']) && !empty($params['identity'])) {
            $where['identity like ?'] = '%' . $params['identity'] . '%';
        }
        if (isset($params['email']) && !empty($params['email'])) {
            $where['email like ?'] = '%' . $params['email'] . '%';
        }
        if (isset($params['mobile']) && !empty($params['mobile'])) {
            $where['mobile like ?'] = '%' . $params['mobile'] . '%';
        }
        if (isset($params['mobiles']) && !empty($params['mobiles'])) {
            $where['mobile'] = $params['mobiles'];
        }
        if (isset($params['status']) && in_array($params['status'], [0, 1])) {
            $where['status'] = $params['status'];
        }
        if (isset($params['id'])) {
            $where['id'] = $params['id'];
        }
        if (isset($params['data_from']) && !empty($params['data_from'])) {
            $where['time_created >= ?'] = $params['data_from'];
        }
        if (isset($params['data_to']) && !empty($params['data_to'])) {
            $where['time_created <= ?'] = $params['data_to'];
        }
        if (isset($params['not_allowed_id']) && !empty($params['not_allowed_id'])) {
            $where[] = new NotIn('id', $params['not_allowed_id']);
        }

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
        // Set columns
        $columns = ['count' => new Expression('count(*)')];

        // Add filter
        $where['time_deleted'] = 0;
        if (isset($params['name']) && !empty($params['name'])) {
            $where['name like ?'] = '%' . $params['name'] . '%';
        }
        if (isset($params['identity']) && !empty($params['identity'])) {
            $where['identity like ?'] = '%' . $params['identity'] . '%';
        }
        if (isset($params['email']) && !empty($params['email'])) {
            $where['email like ?'] = '%' . $params['email'] . '%';
        }
        if (isset($params['mobile']) && !empty($params['mobile'])) {
            $where['mobile like ?'] = '%' . $params['mobile'] . '%';
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $where['id'] = $params['id'];
        }
        if (isset($params['status']) && in_array($params['status'], [0, 1])) {
            $where['status'] = $params['status'];
        }
        if (isset($params['data_from']) && !empty($params['data_from'])) {
            $where['time_created >= ?'] = $params['data_from'];
        }
        if (isset($params['data_to']) && !empty($params['data_to'])) {
            $where['time_created <= ?'] = $params['data_to'];
        }
        if (isset($params['not_allowed_id']) && !empty($params['not_allowed_id'])) {
            $where[] = new NotIn('id', $params['not_allowed_id']);
        }

        // Get count
        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableAccount)->columns($columns)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $row       = $statement->execute()->current();

        return (int)$row['count'];
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
        if (isset($params['not_allowed_id']) && !empty($params['not_allowed_id'])) {
            $where[] = new NotIn('id', $params['not_allowed_id']);
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

    public function getAccountPassword(int $userId): string|null
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

    public function duplicatedAccount(array $params = []): int
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

    public function getProfile(array $params = []): array|object
    {
        // Set
        $where = [];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id'] = (int)$params['id'];
        } elseif (isset($params['user_id']) && (int)$params['user_id'] > 0) {
            $where['user_id'] = $params['user_id'];
        } elseif (isset($params['item_id']) && (int)$params['item_id'] > 0) {
            $where['item_id'] = $params['item_id'];
        }
        if (isset($params['not_allowed_id']) && !empty($params['not_allowed_id'])) {
            $where[] = new NotIn('id', $params['not_allowed_id']);
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

    public function getAccountProfileList($params = []): HydratingResultSet
    {
        $where['account.time_deleted'] = 0;
        if (isset($params['name']) && !empty($params['name'])) {
            $where['account.name like ?'] = '%' . $params['name'] . '%';
        }
        if (isset($params['identity']) && !empty($params['identity'])) {
            $where['account.identity like ?'] = '%' . $params['identity'] . '%';
        }
        if (isset($params['email']) && !empty($params['email'])) {
            $where['account.email like ?'] = '%' . $params['email'] . '%';
        }
        if (isset($params['mobile']) && !empty($params['mobile'])) {
            $where['account.mobile like ?'] = '%' . $params['mobile'] . '%';
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $where['account.id'] = $params['id'];
        }
        if (isset($params['status']) && in_array($params['status'], [0, 1])) {
            $where['account.status'] = $params['status'];
        }
        if (isset($params['data_from']) && !empty($params['data_from'])) {
            $where['account.time_created >= ?'] = $params['data_from'];
        }
        if (isset($params['data_to']) && !empty($params['data_to'])) {
            $where['account.time_created <= ?'] = $params['data_to'];
        }
        if (isset($params['not_allowed_id']) && !empty($params['not_allowed_id'])) {
            $where[] = new NotIn('account.id', $params['not_allowed_id']);
        }

        $sql    = new Sql($this->db);
        $select = $sql->select();
        $select->from(['account' => $this->tableAccount])->where($where)->order($params['order'])->offset($params['offset'])->limit($params['limit']);;
        $select->join(
            ['profile' => $this->tableProfile],
            'account.id=profile.user_id',
            [
                'first_name',
                'last_name',
                'avatar',
                'birthdate',
                'gender',
                'information',
            ],
            $select::JOIN_LEFT . ' ' . $select::JOIN_OUTER
        );
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->accountProfilePrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    public function getAccountProfile($params = []): object|array
    {
        // Set
        $where = [];
        if (isset($params['id']) && !empty($params['id'])) {
            $where['account.id'] = $params['id'];
        }
        if (isset($params['identity']) && !empty($params['identity'])) {
            $where['account.identity'] = $params['identity'];
        }
        if (isset($params['email']) && !empty($params['email'])) {
            $where['account.email'] = $params['email'];
        }
        if (isset($params['mobile']) && !empty($params['mobile'])) {
            $where['account.mobile'] = $params['mobile'];
        }

        $sql    = new Sql($this->db);
        $select = $sql->select();
        $select->from(['account' => $this->tableAccount])->where($where);
        $select->join(
            ['profile' => $this->tableProfile],
            'account.id=profile.user_id',
            [
                'first_name',
                'last_name',
                'avatar',
                'birthdate',
                'gender',
            ],
            $select::JOIN_LEFT . ' ' . $select::JOIN_OUTER
        );

        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->accountProfilePrototype);
        $resultSet->initialize($result);
        $profile = $resultSet->current();

        if (!$profile) {
            return [];
        }

        return $profile;
    }

    public function authentication($identityColumn = 'identity', $credentialColumn = 'credential', $hashPattern = 'argon2id'): AuthenticationService
    {
        // Call authAdapter
        $authAdapter = new CallbackCheckAdapter(
            $this->db,
            $this->tableAccount,
            $identityColumn,
            $credentialColumn,
            function ($hash, $password) use ($hashPattern) {
                $result = false;
                switch ($hashPattern) {
                    case'argon2id':
                    case'bcrypt':
                        $result = password_verify($password, $hash);
                        break;

                    case'sha512':
                        $result = hash_equals($hash, hash('sha512', $password));
                        break;
                }
                return $result;
            }
        );

        // Set condition
        $select = $authAdapter->getDbSelect();
        $select->where(['status' => 1, new IsNotNull($credentialColumn)]);

        return new AuthenticationService(null, $authAdapter);
    }

    public function authenticationOauth($params): AuthenticationResult
    {
        // Get an account
        $account = $this->getAccount(['email' => $params['email']/*, 'status' => 1*/]);
        if ($account) {
            return new AuthenticationResult(AuthenticationResult::SUCCESS, $account);
        }

        return new AuthenticationResult(AuthenticationResult::FAILURE, null, ['Invalid email']);
    }

    public function authenticationOauth2($params): AuthenticationResult
    {
        // Get an account
        $account = $this->getAccount(['identity' => $params['identity']/*, 'status' => 1*/]);
        if ($account) {
            return new AuthenticationResult(AuthenticationResult::SUCCESS, $account);
        }

        return new AuthenticationResult(AuthenticationResult::FAILURE, null, ['Invalid identity']);
    }

    public function getIdFromFilter(array $filter = []): HydratingResultSet|array
    {
        $where  = [];
        $sql    = new Sql($this->db);
        $select = $sql->select($this->tableRoleAccount)->where($where);

        switch ($filter['type']) {
            case 'string':
                $select->where(['role' => $filter['value']]);
                break;
        }

        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->accountRolePrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    public function getMultiFactor(int $userId): array|null
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

        $resultSet = new HydratingResultSet($this->hydrator, $this->multiFactorPrototype);
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

        return [
            'id'                  => $account->getId(),
            'multi_factor_status' => $account->getMultiFactorStatus(),
            'multi_factor_secret' => $account->getMultiFactorSecret(),
        ];
    }
}