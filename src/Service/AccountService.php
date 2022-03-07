<?php

namespace User\Service;

use Laminas\Crypt\Password\Bcrypt;
use User\Repository\AccountRepositoryInterface;

class AccountService implements ServiceInterface
{
    /**
     * @var AccountRepositoryInterface
     */
    protected AccountRepositoryInterface $accountRepository;

    /**
     * @var TokenService
     */
    protected TokenService $tokenService;


    public function __construct(
        AccountRepositoryInterface $accountRepository,
        TokenService $tokenService
    ) {
        $this->accountRepository = $accountRepository;
        $this->tokenService      = $tokenService;
    }

    public function authentication($params): array
    {
        // Do login
        $authentication = $this->accountRepository->authentication();
        $adapter        = $authentication->getAdapter();
        $adapter->setIdentity($params['identity'])->setCredential($params['credential']);

        // Check login
        if ($authentication->authenticate()->isValid()) {
            // Get user account
            $account = (array)$adapter->getResultRowObject(
                [
                    'id',
                    'name',
                    'email',
                    'identity',
                ]
            );

            // Generate access token
            $account['access_token'] = $this->tokenService->generate(
                [
                    'user_id' => $account['id'],
                    'type'    => 'access',
                    'roles'   => [
                        'member',
                    ],
                ]
            );

            // Generate refresh token
            $account['refresh_token'] = $this->tokenService->generate(
                [
                    'user_id' => $account['id'],
                    'type'    => 'refresh',
                    'roles'   => [
                        'member',
                    ],
                ]
            );

            $result = [
                'result' => 'true',
                'data'   => $account,
                'error'  => '',
            ];
        } else {
            $result = [
                'result' => 'false',
                'data'   => [],
                'error'  => 'error in login',
            ];
        }

        return $result;
    }

    public function getAccount($params): array
    {
        $account = $this->accountRepository->getAccount($params);

        return [
            'id'       => $account->getId(),
            'name'     => $account->getName(),
            'identity' => $account->getIdentity(),
            'email'    => $account->getEmail(),
        ];
    }

    public function addAccount($params): array
    {
        $params['credential']   = $this->generateCredential($params['credential']);
        $params['status']       = $this->userRegisterStatus();
        $params['time_created'] = time();

        $account = $this->accountRepository->addAccount($params);

        return [
            'id'       => $account->getId(),
            'name'     => $account->getName(),
            'identity' => $account->getIdentity(),
            'email'    => $account->getEmail(),
            'status'   => $account->getStatus(),
        ];
    }

    public function updateAccount($params, $account): array
    {
        // Clean up
        foreach ($params as $key => $value) {
            if (empty($value) || !isset($account[$key]) || $account[$key] == $value) {
                unset($params[$key]);
            }
        }

        if (!empty($params)) {
            $this->accountRepository->updateAccount((int)$account['id'], $params);
        }

        return $this->getAccount(['id' => (int)$account['id']]);
    }

    public function updatePassword($params, $account): array
    {
        $hash = $this->accountRepository->getAccountCredential((int)$account['id']);

        $bcrypt = new Bcrypt();
        if ($bcrypt->verify($params['current_credential'], $hash)) {
            $credential = $bcrypt->create($params['new_credential']);

            $this->accountRepository->updateAccount((int)$account['id'], ['credential' => $credential]);

            $result = [
                'result' => true,
                'data'   => [
                    'message' => 'Password update successfully !'
                ],
                'error'  => '',
            ];
        } else {
            $result = [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error to update password !'
                ],
            ];
        }

        return $result;
    }

    public function generateCredential($credential): string
    {
        $bcrypt = new Bcrypt();
        return $bcrypt->create($credential);
    }

    public function isDuplicated($type, $value, $id = 0): bool
    {
        return (bool)$this->accountRepository->count(
            [
                'field' => $type,
                'value' => $value,
                'id'    => $id,
            ]
        );
    }

    public function userRegisterStatus(): int
    {
        // ToDo: call it from config
        return 1;
    }
}