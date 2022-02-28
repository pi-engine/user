<?php

namespace User\Repository;

use InvalidArgumentException;
use Laminas\Authentication\Adapter\DbTable\CallbackCheckAdapter;
use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Hydrator\HydratorInterface;
use RuntimeException;
use User\Model\Account;

class AccountRepository implements AccountRepositoryInterface
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
     * @var Account
     */
    private Account $accountPrototype;

    public function __construct(
        AdapterInterface $db,
        HydratorInterface $hydrator,
        Account $accountPrototype
    ) {
        $this->db               = $db;
        $this->hydrator         = $hydrator;
        $this->accountPrototype = $accountPrototype;
    }

    public function getAccounts($params = [])
    {
        // TODO: Implement getAccounts() method.

        $sql       = new Sql($this->db);
        $select    = $sql->select('account');
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->accountPrototype);
        $resultSet->initialize($result);
        return $resultSet;
    }

    public function getAccount(array $params = []): Account
    {
        $sql       = new Sql($this->db);
        $select    = $sql->select('account')->where(['id' => $params['id']]);
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

    public function addAccount(array $params = []): Account
    {
        $insert = new Insert('account');
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

    public function count(array $params = []): int
    {
        // Set where
        $columns = ['count' => new Expression('count(*)')];
        $where   = [$params['field'] => $params['value']];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id <> ?'] = $params['id'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select('account')->columns($columns)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $row       = $statement->execute()->current();

        return (int)$row['count'];
    }

    public function authentication(): AuthenticationService
    {
        // Call authAdapter
        $authAdapter = new CallbackCheckAdapter(
            $this->db,
            'account',
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