<?php

namespace User\Service;

use Laminas\Crypt\Password\Bcrypt;
use Laminas\Math\Rand;
use Laminas\Soap\Client as LaminasSoapClient;
use Psr\SimpleCache\InvalidArgumentException;
use User\Repository\AccountRepositoryInterface;
use function array_merge;
use function in_array;
use function is_object;
use function is_string;
use function sprintf;
use function str_replace;
use function time;

class AccountService implements ServiceInterface
{
    /* @var AccountRepositoryInterface */
    protected AccountRepositoryInterface $accountRepository;

    /* @var RoleService */
    protected RoleService $roleService;

    /* @var TokenService */
    protected TokenService $tokenService;

    /* @var CacheService */
    protected CacheService $cacheService;

    protected array $profileFields
        = [
            'user_id',
            'first_name',
            'last_name',
            'id_number',
            'birthdate',
            'gender',
            'avatar',
            'ip_register',
            'register_source',
            'homepage',
            'phone',
            'address_1',
            'address_2',
            'country',
            'state',
            'city',
            'zip_code',
            'bank_name',
            'bank_card',
            'bank_account',
        ];

    /**
     * @param AccountRepositoryInterface $accountRepository
     * @param TokenService               $tokenService
     * @param CacheService               $cacheService
     */
    public function __construct(
        AccountRepositoryInterface $accountRepository,
        RoleService $roleService,
        TokenService $tokenService,
        CacheService $cacheService
    ) {
        $this->accountRepository = $accountRepository;
        $this->roleService       = $roleService;
        $this->tokenService      = $tokenService;
        $this->cacheService      = $cacheService;
    }

    /**
     * @param $params
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function login($params): array
    {
        // Set login column
        $identityColumn = $params['column'] ?? 'identity';

        // Do log in
        $authentication = $this->accountRepository->authentication($identityColumn);
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
                    'status',
                ]
            );

            // Canonize account
            $account = $this->canonizeAccount($account);

            // Get roles
            $account['roles'] = $this->roleService->getRoleAccount($account['id']);

            // Generate access token
            $accessToken = $this->tokenService->generate(
                [
                    'user_id' => $account['id'],
                    'type'    => 'access',
                    'roles'   => $account['roles'],
                ]
            );

            // Generate refresh token
            $refreshToken = $this->tokenService->generate(
                [
                    'user_id' => $account['id'],
                    'type'    => 'refresh',
                    'roles'   => $account['roles'],
                ]
            );

            // Set extra info
            $account['last_login']    = time();
            $account['access_token']  = $accessToken['token'];
            $account['refresh_token'] = $refreshToken['token'];

            // Get from cache if exist
            $user = $this->cacheService->getUser($account['id']);

            // Set/Update user data to cache
            $this->cacheService->setUser($account['id'], [
                'account'      => [
                    'id'         => $account['id'],
                    'name'       => $account['name'],
                    'email'      => $account['email'],
                    'identity'   => $account['identity'],
                    'mobile'     => $account['mobile'],
                    'last_login' => $account['last_login'],
                ],
                'roles'        => $account['roles'],
                'access_keys'  => (isset($user['access_keys']) && !empty($user['access_keys']))
                    ? array_unique(array_merge($user['access_keys'], [$accessToken['key']]))
                    : [$accessToken['key']],
                'refresh_keys' => (isset($user['refresh_keys']) && !empty($user['refresh_keys']))
                    ? array_unique(array_merge($user['refresh_keys'], [$refreshToken['key']]))
                    : [$refreshToken['key']],
            ]);

            $result = [
                'result' => true,
                'data'   => $account,
                'error'  => [],
            ];
        } else {
            $result = [
                'result' => false,
                'data'   => [],
                'error'  => 'error in login',
            ];
        }

        return $result;
    }

    public function logout($params): array
    {
        if (isset($params['all_session']) && (int)$params['all_session'] == 1) {
            $this->cacheService->deleteUser($params['user_id']);
            $message = 'You are logout successfully from all of your sessions !';
        } else {
            $this->cacheService->removeItem($params['user_id'], 'access_keys', $params['token_id']);
            $message = 'You are logout successfully from this session !';
        }

        return [
            'result' => true,
            'data'   => [
                'message' => $message,
            ],
            'error'  => [],
        ];
    }

    public function prepareMobileLogin($params): array
    {
        // Set new password as OTP
        $otpCode   = Rand::getInteger(100000, 999999);
        $otpExpire = (time() + 120);
        $isNew     = 0;

        // Check account exist
        $account = $this->getAccount(['mobile' => $params['mobile']]);

        // Create account if not exist
        // Update OTP password if account exist
        if (empty($account)) {
            $account = $this->addAccount(
                [
                    'mobile'     => $params['mobile'],
                    'credential' => $otpCode,
                ]
            );

            // Set is new
            $isNew = 1;
        } else {
            $bcrypt     = new Bcrypt();
            $credential = $bcrypt->create($otpCode);
            $this->accountRepository->updateAccount((int)$account['id'], ['credential' => $credential]);
        }

        // Set user cache
        $this->cacheService->setUser(
            $account['id'],
            [
                'account' => [
                    'id'         => $account['id'],
                    'name'       => $account['name'],
                    'email'      => $account['email'],
                    'identity'   => $account['identity'],
                    'mobile'     => $account['mobile'],
                    'last_login' => isset($user['account']['last_login']) ?? time(),
                ],
                'otp'     => [
                    'code'        => $otpCode,
                    'time_expire' => $otpExpire,
                ],
            ]
        );

        // Set sms message
        $message = 'کد تایید: %s
        لوکس ایرانا';

        // Send OTP as SMS
        $this->sendSMS(
            [
                'message' => sprintf($message, $otpCode),
                'mobile'  => $account['mobile'],
            ]
        );

        // Set result
        return [
            'result' => true,
            'data'   => [
                'message'    => 'Verify code send to your mobile number !',
                'name'       => $account['name'],
                'mobile'     => $account['mobile'],
                'is_new'     => $isNew,
                'otp_expire' => $otpExpire,
            ],
            'error'  => [],
        ];
    }

    public function getAccountCount($params = []): int
    {
        return $this->accountRepository->getAccountCount($params);
    }

    public function getAccountList($params): array
    {
        $limit  = (int)$params['limit'] ?? 10;
        $page   = (int)$params['page'] ?? 1;
        $order  = $params['order'] ?? ['time_created DESC', 'id DESC'];
        $offset = ($page - 1) * $limit;

        // Set params
        $listParams = [
            'page'   => $page,
            'limit'  => $limit,
            'order'  => $order,
            'offset' => $offset,
        ];

        // Get list
        $list   = [];
        $rowSet = $this->accountRepository->getAccountList($listParams);
        foreach ($rowSet as $row) {
            $list[] = $this->canonizeAccount($row);
        }

        // Get count
        $count = $this->accountRepository->getAccountCount($listParams);

        return [
            'list'      => $list,
            'paginator' => [
                'count' => $count,
                'limit' => $limit,
                'page'  => $page,
            ],
        ];
    }

    public function getAccount($params): array
    {
        $account = $this->accountRepository->getAccount($params);
        return $this->canonizeAccount($account);
    }

    public function getProfile($params): array
    {
        $profile = $this->accountRepository->getProfile($params);
        return $this->canonizeProfile($profile);
    }

    public function addAccount($params): array
    {
        // Set name
        if (
            isset($params['first_name'])
            && !empty($params['first_name'])
            && isset($params['last_name'])
            && !empty($params['last_name'])
        ) {
            $params['name'] = sprintf('%s %s', $params['first_name'], $params['last_name']);
        }

        $credential = null;
        if (isset($params['credential']) && !empty($params['credential'])) {
            $credential = $this->generateCredential($params['credential']);
        }

        $paramsAccount = [
            'name'         => $params['name'] ?? null,
            'email'        => $params['email'] ?? null,
            'identity'     => $params['identity'] ?? null,
            'mobile'       => $params['mobile'] ?? null,
            'credential'   => $credential,
            'status'       => $this->userRegisterStatus(),
            'time_created' => time(),
        ];

        $account = $this->accountRepository->addAccount($paramsAccount);
        $account = $this->canonizeAccount($account);

        $profileParams = [
            'user_id'         => (int)$account['id'],
            'first_name'      => $params['first_name'] ?? null,
            'last_name'       => $params['last_name'] ?? null,
            'id_number'       => $params['id_number'] ?? null,
            'birthdate'       => $params['birthdate'] ?? null,
            'gender'          => $params['gender'] ?? null,
            'avatar'          => $params['avatar'] ?? null,
            'ip_register'     => $params['ip_register'] ?? null,
            'register_source' => $params['register_source'] ?? null,
            'homepage'        => $params['homepage'] ?? null,
            'phone'           => $params['phone'] ?? null,
            'address_1'       => $params['address_1'] ?? null,
            'address_2'       => $params['address_2'] ?? null,
            'country'         => $params['country'] ?? null,
            'state'           => $params['state'] ?? null,
            'city'            => $params['city'] ?? null,
            'zip_code'        => $params['zip_code'] ?? null,
            'bank_name'       => $params['bank_name'] ?? null,
            'bank_card'       => $params['bank_card'] ?? null,
            'bank_account'    => $params['bank_account'] ?? null,
        ];

        $profile = $this->accountRepository->addProfile($profileParams);
        $profile = $this->canonizeProfile($profile);

        $account = array_merge($account, $profile);

        // Set user roles
        $this->roleService->addDefaultRoles((int)$account['id']);

        return $account;
    }

    public function updateAccount($params, $account): array
    {
        // Set name
        if (
            isset($params['first_name'])
            && !empty($params['first_name'])
            && isset($params['last_name'])
            && !empty($params['last_name'])
        ) {
            $params['name'] = sprintf('%s %s', $params['first_name'], $params['last_name']);
        }

        // Clean up
        $accountParams = [];
        $profileParams = [];
        foreach ($params as $key => $value) {
            if (!empty($value) && isset($account[$key]) && $account[$key] != $value) {
                $accountParams[$key] = $value;
            }
            if (in_array($key, $this->profileFields)) {
                if (empty($value)) {
                    $profileParams[$key] = null;
                } elseif (is_string($value)) {
                    $profileParams[$key] = $value;
                }
            }
        }

        if (!empty($accountParams)) {
            $this->accountRepository->updateAccount((int)$account['id'], $accountParams);
        }

        if (!empty($profileParams)) {
            $this->accountRepository->updateProfile((int)$account['id'], $profileParams);
        }

        // Get account after update
        $account = $this->getAccount(['id' => (int)$account['id']]);
        $profile = $this->getProfile(['user_id' => (int)$account['id']]);

        // Get user from cache if exist
        $user = $this->cacheService->getUser($account['id']);

        // Set/Update user data to cache
        $this->cacheService->setUser(
            $account['id'],
            [
                'account' => [
                    'id'         => $account['id'],
                    'name'       => $account['name'],
                    'email'      => $account['email'],
                    'identity'   => $account['identity'],
                    'mobile'     => $account['mobile'],
                    'last_login' => isset($user['account']['last_login']) ?? time(),
                ],
            ]
        );

        return array_merge($account, $profile);
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
                    'message' => 'Password update successfully !',
                ],
                'error'  => [],
            ];
        } else {
            $result = [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error to update password !',
                ],
            ];
        }

        return $result;
    }

    public function refreshToken($params): array
    {
        $accessToken = $this->tokenService->generate(
            [
                'user_id' => $params['user_id'],
                'type'    => 'access',
                'roles'   => [
                    'member',
                ],
            ]
        );

        // Update cache
        $this->cacheService->addItem($params['user_id'], 'access_keys', $accessToken['key']);

        // Set result array
        return [
            'result' => true,
            'data'   => [
                'access_token' => $accessToken['token'],
            ],
            'error'  => [],
        ];
    }

    public function canonizeAccount($account): array
    {
        if (empty($account)) {
            return [];
        }

        if (is_object($account)) {
            $account = [
                'id'       => $account->getId(),
                'name'     => $account->getName(),
                'identity' => $account->getIdentity(),
                'email'    => $account->getEmail(),
                'mobile'   => $account->getMobile(),
                'status'   => $account->getStatus(),
            ];
        } else {
            $account = [
                'id'       => $account['id'],
                'name'     => $account['name'] ?? '',
                'email'    => $account['email'] ?? '',
                'identity' => $account['identity'] ?? '',
                'mobile'   => $account['mobile'] ?? '',
                'status'   => $account['status'],
            ];
        }

        return $account;
    }

    public function canonizeProfile($profile): array
    {
        if (empty($profile)) {
            return [];
        }

        if (is_object($profile)) {
            $profile = [
                'first_name'      => $profile->getFirstName(),
                'last_name'       => $profile->getLastName(),
                'id_number'       => $profile->getIdNumber(),
                'birthdate'       => $profile->getBirthdate(),
                'gender'          => $profile->getGender(),
                'avatar'          => $profile->getAvatar(),
                'ip_register'     => $profile->getIpRegister(),
                'register_source' => $profile->getRegisterSource(),
                'homepage'        => $profile->getHomepage(),
                'phone'           => $profile->getPhone(),
                'address_1'       => $profile->getAddress1(),
                'address_2'       => $profile->getAddress2(),
                'country'         => $profile->getCountry(),
                'state'           => $profile->getState(),
                'city'            => $profile->getCity(),
                'zip_code'        => $profile->getZipCode(),
                'bank_name'       => $profile->getBankName(),
                'bank_card'       => $profile->getBankCard(),
                'bank_account'    => $profile->getBankAccount(),
            ];
        } else {
            $profile = [
                'first_name'      => $profile['first_name'],
                'last_name'       => $profile['last_name'],
                'id_number'       => $profile['id_number'],
                'birthdate'       => $profile['birthdate'],
                'gender'          => $profile['gender'],
                'avatar'          => $profile['avatar'],
                'ip_register'     => $profile['ip_register'],
                'register_source' => $profile['register_source'],
                'homepage'        => $profile['homepage'],
                'phone'           => $profile['phone'],
                'address_1'       => $profile['address_1'],
                'address_2'       => $profile['address_2'],
                'country'         => $profile['country'],
                'state'           => $profile['state'],
                'city'            => $profile['city'],
                'zip_code'        => $profile['zip_code'],
                'bank_name'       => $profile['bank_name'],
                'bank_card'       => $profile['bank_card'],
                'bank_account'    => $profile['bank_account'],
            ];
        }

        return $profile;
    }

    public function generateCredential($credential): string
    {
        $bcrypt = new Bcrypt();
        return $bcrypt->create($credential);
    }

    public function userRegisterStatus(): int
    {
        // ToDo: call it from config
        return 1;
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

    // ToDo: Move it to notification module
    public function sendSMS($params)
    {

    }
}