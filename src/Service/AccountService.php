<?php

declare(strict_types=1);

namespace Pi\User\Service;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Authentication\Adapter\DbTable\CallbackCheckAdapter;
use Laminas\Filter\StringToLower;
use Pi\Core\Security\Account\AccountLocked;
use Pi\Core\Security\Account\AccountLoginAttempts;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\SignatureService;
use Pi\Core\Service\TranslatorService;
use Pi\Core\Service\UtilityService;
use Pi\Notification\Service\NotificationService;
use Pi\User\Repository\AccountRepositoryInterface;
use Random\RandomException;
use function array_merge;
use function in_array;
use function is_object;
use function is_string;
use function sprintf;
use function time;

class AccountService implements ServiceInterface
{
    /* @var AccountRepositoryInterface */
    protected AccountRepositoryInterface $accountRepository;

    /* @var RoleService */
    protected RoleService $roleService;

    /** @var PermissionService */
    protected PermissionService $permissionService;

    /* @var TokenService */
    protected TokenService $tokenService;

    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /** @var NotificationService */
    protected NotificationService $notificationService;

    /** @var HistoryService */
    protected HistoryService $historyService;

    /** @var TranslatorService */
    protected TranslatorService $translatorService;

    /** @var AccountLoginAttempts */
    protected AccountLoginAttempts $accountLoginAttempts;

    /** @var AccountLocked */
    protected AccountLocked $accountLocked;

    /** @var SignatureService */
    protected SignatureService $signatureService;

    protected ?\Grc\Company\Service\CompanyLightService $companyService = null;

    /* @var array */
    protected array $config;

    /* @var string */
    protected string $identityColumn = 'identity';

    /* @var string */
    protected string $credentialColumn = 'credential';

    /* @var array */
    protected array $accountFields
        = [
            'name',
            //'email',
            //'identity',
            //'mobile',
            //'status',
            'multi_factor_status',
            'multi_factor_method',
            'multi_factor_secret',
        ];

    /* @var array */
    protected array $profileFields
        = [
            'user_id',
            'first_name',
            'last_name',
            'birthdate',
            'gender',
            'avatar',
        ];

    /* @var array */
    protected array $informationFields
        = [
            'avatar_params',
            'ip_register',
            'register_source',
            'id_number',
            'homepage',
            'phone',
            'address_1',
            'address_2',
            'item_id',
            'country',
            'state',
            'city',
            'zip_code',
            'bank_name',
            'bank_card',
            'bank_account',
            'device_type',
            'device_token',
        ];

    protected array $emptyRoles = ['api' => [], 'admin' => []];

    protected string $onlineSessionsKey = 'online_sessions';

    protected int $onlineTimeout = 900; // 15 minutes (900 seconds)

    /* @var string */
    protected string $hashPattern = 'argon2id';

    /**
     * @param AccountRepositoryInterface $accountRepository
     * @param RoleService                $roleService
     * @param PermissionService          $permissionService
     * @param TokenService               $tokenService
     * @param CacheService               $cacheService
     * @param UtilityService             $utilityService
     * @param NotificationService        $notificationService
     * @param HistoryService             $historyService
     * @param TranslatorService          $translatorService
     * @param AccountLoginAttempts       $accountLoginAttempts
     * @param AccountLocked              $accountLocked
     * @param SignatureService           $signatureService
     * @param                            $config
     */
    public function __construct(
        AccountRepositoryInterface $accountRepository,
        RoleService                $roleService,
        PermissionService          $permissionService,
        TokenService               $tokenService,
        CacheService               $cacheService,
        UtilityService             $utilityService,
        NotificationService        $notificationService,
        HistoryService             $historyService,
        TranslatorService          $translatorService,
        AccountLoginAttempts       $accountLoginAttempts,
        AccountLocked              $accountLocked,
        SignatureService           $signatureService,
                                   $config
    ) {
        $this->accountRepository    = $accountRepository;
        $this->roleService          = $roleService;
        $this->permissionService    = $permissionService;
        $this->tokenService         = $tokenService;
        $this->cacheService         = $cacheService;
        $this->utilityService       = $utilityService;
        $this->notificationService  = $notificationService;
        $this->historyService       = $historyService;
        $this->translatorService    = $translatorService;
        $this->accountLoginAttempts = $accountLoginAttempts;
        $this->accountLocked        = $accountLocked;
        $this->signatureService     = $signatureService;
        $this->config               = $config;
    }

    /**
     * @param $companyService
     *
     * @return void
     */
    public function setCompanyService($companyService): void
    {
        $this->companyService = $companyService;
    }

    /**
     * @return bool
     */
    public function hasCompanyService(): bool
    {
        return $this->companyService !== null;
    }

    /**
     * @param       $params
     *
     * @return array
     * @throws RandomException
     */
    public function login($params): array
    {
        // Set login column
        $this->identityColumn   = $params['identityColumn'] ?? $this->identityColumn;
        $this->credentialColumn = $params['credentialColumn'] ?? $this->credentialColumn;

        // Perform authentication
        $authentication = $this->accountRepository->authentication($this->identityColumn, $this->credentialColumn, $this->hashPattern);

        // Set authentication adapter
        /** @var CallbackCheckAdapter $adapter */
        $adapter = $authentication->getAdapter();
        $adapter->setIdentity($params['identity'])->setCredential($params['credential']);

        // Check login
        if ($authentication->authenticate()->isValid()) {
            // Get a user account
            $account = (array)$adapter->getResultRowObject(
                [
                    'id',
                    'name',
                    'email',
                    'mobile',
                    'identity',
                    'status',
                    'time_created',
                    'multi_factor_status',
                    'multi_factor_method',
                    'multi_factor_secret',
                ]
            );

            // Canonize account
            $account = $this->canonizeAccount($account);

            // Complete login
            $result = $this->postLoginSuccess($account, $params);
        } else {
            $result = $this->postLoginError($params);
        }

        return $result;
    }

    /**
     * @param       $params
     *
     * @return array
     * @throws RandomException
     */
    public function loginOauth($params): array
    {
        // Set login column
        $this->identityColumn = 'email';

        // Do log in
        $authAdapter = $this->accountRepository->authenticationOauth($params);

        // Check login
        if ($authAdapter->isValid()) {
            // Get a user account
            $account = $authAdapter->getIdentity();

            // Canonize account
            $account = $this->canonizeAccount($account);

            // Complete login
            $result = $this->postLoginSuccess($account, $params);
        } else {
            if (isset($this->config['oauth']['oauth_register']) && (int)$this->config['oauth']['oauth_register'] === 1) {
                $this->addAccount($params);
                $result = $this->loginOauth($params);
            } else {
                $result = $this->postLoginError($params);
            }
        }

        return $result;
    }

    /**
     * @param       $params
     *
     * @return array
     * @throws RandomException
     */
    public function loginOauth2($params): array
    {
        // Set login column
        $this->identityColumn = 'identity';

        // Do log in
        $authAdapter = $this->accountRepository->authenticationOauth2($params);

        // Check login
        if ($authAdapter->isValid()) {
            // Get a user account
            $account = $authAdapter->getIdentity();

            // Canonize account
            $account = $this->canonizeAccount($account);

            // Complete login
            $result = $this->postLoginSuccess($account, $params);
        } else {
            if (isset($this->config['oauth']['oauth_register']) && (int)$this->config['oauth']['oauth_register'] === 1) {
                $this->addAccount($params);
                $result = $this->loginOauth2($params);
            } else {
                $result = $this->postLoginError($params);
            }
        }

        return $result;
    }

    /**
     * @param       $account
     * @param       $params
     *
     * @return array
     * @throws RandomException
     */
    public function postLoginSuccess($account, $params): array
    {
        // Check account signature
        if ($this->config['login']['check_signature']) {
            if (!$this->signatureService->checkSignature('user_account', ['id' => $account['id']])) {
                return [
                    'result' => false,
                    'data'   => [],
                    'error'  => [
                        'message' => 'Signature validation failed: one or more fields have been modified or corrupted.',
                        'key'     => 'signature-validation-failed',
                    ],
                    'status' => StatusCodeInterface::STATUS_UNAUTHORIZED,
                ];
            }
        }

        // Set account lock params
        $lockParams = [
            'type'            => 'id',
            'user_id'         => (int)$account['id'],
            'security_stream' => $params['security_stream'],
        ];

        // Check account is lock or not
        if ($this->accountLocked->isLocked($lockParams)) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => $this->accountLocked->getErrorMessage(),
                ],
                'status' => $this->accountLocked->getStatusCode(),
            ];
        }

        // Get from cache if exist
        $user = $this->cacheService->getUser((int)$account['id']);

        // Set multi factor
        $multiFactorGlobal = (int)$this->config['multi_factor']['status'] ?? 0;
        $multiFactorStatus = (int)$account['multi_factor_status'] ?? 0;
        $multiFactorMethod = $account['multi_factor_method'] ?? $this->config['multi_factor']['default_method'] ?? null;
        $multiFactorVerify = 0;
        unset($account['multi_factor_status']); // ToDo: check it needed to unset
        unset($account['multi_factor_method']); // ToDo: check it needed to unset

        // Get profile
        $profile = $this->getProfile(['user_id' => (int)$account['id']]);

        // Sync profile and account
        $account = array_merge($account, $profile);

        // Set baseurl
        $account['baseurl'] = $this->config['baseurl'] ?? '';

        // Get roles
        $account['roles']      = $this->roleService->getRoleAccount((int)$account['id']);
        $account['roles_full'] = $this->roleService->canonizeAccountRole($account['roles']);

        // Set company data and Get company details if company module loaded
        $account['company_id']    = $user['authorization']['company_id'] ?? 0;
        $account['company_title'] = $user['authorization']['company']['title'] ?? '';
        if ($this->hasCompanyService()) {
            $company = $this->companyService->getCompanyDetails((int)$account['id']);
            if (!empty($company)) {
                $account['company_id']    = $company['company_id'];
                $account['company_title'] = $company['company_title'];
            }
        }

        // Generate access token
        $accessToken = $this->tokenService->encryptToken(
            [
                'account' => $account,
                'type'    => 'access',
                'origin'  => $params['security_stream']['origin']['data']['origin'] ?? 'public',
                'ip'      => $params['security_stream']['ip']['data']['client_ip'] ?? '',
                'aud'     => $params['security_stream']['url']['data']['client_url'] ?? '',
            ]
        );

        // Generate refresh token
        $refreshToken = $this->tokenService->encryptToken(
            [
                'account' => $account,
                'type'    => 'refresh',
                'id'      => $accessToken['id'],
                'origin'  => $params['security_stream']['origin']['data']['origin'] ?? 'public',
                'ip'      => $params['security_stream']['ip']['data']['client_ip'] ?? '',
                'aud'     => $params['security_stream']['url']['data']['client_url'] ?? '',
            ]
        );

        // Set multi factor
        $multiFactor = [
            $accessToken['id'] => [
                'id'                  => $accessToken['id'],
                'create'              => $accessToken['payload']['iat'],
                'expire'              => $accessToken['payload']['exp'],
                'multi_factor_global' => $multiFactorGlobal,
                'multi_factor_status' => $multiFactorStatus,
                'multi_factor_method' => $multiFactorMethod,
                'multi_factor_verify' => $multiFactorVerify,
            ],
        ];

        // Set extra info
        $account['last_login']          = time();
        $account['last_login_view']     = $this->utilityService->date($account['last_login']);
        $account['access_ip']           = $params['security_stream']['ip']['data']['client_ip'];
        $account['origin']              = $params['security_stream']['origin']['data']['origin'];
        $account['has_password']        = $this->hasPassword($account['id']);
        $account['multi_factor_global'] = $multiFactorGlobal;
        $account['multi_factor_status'] = $multiFactorStatus;
        $account['multi_factor_method'] = $multiFactorMethod;
        $account['multi_factor_verify'] = $multiFactorVerify;
        $account['access_token']        = $accessToken['token'];
        $account['refresh_token']       = $refreshToken['token'];
        $account['permission']          = [];
        $account['token_payload']       = [
            'iat' => $accessToken['payload']['iat'],
            'exp' => $accessToken['payload']['exp'],
            'ttl' => $accessToken['ttl'],
        ];

        // Set permission
        if (isset($this->config['login']['permission']) && (int)$this->config['login']['permission'] === 1) {
            // Set permission params
            $permissionParams = [
                'section' => $this->config['login']['permission_role'] ?? 'api',
                'role'    => $account['roles'],
            ];

            // Get permission list
            $account['permission'] = $this->permissionService->getPermissionRole($permissionParams);

            // Check and cleanup permission list
            if (
                isset($this->config['login']['permission_blacklist'])
                && !empty($this->config['login']['permission_blacklist'])
            ) {
                $account['permission'] = array_values(array_diff($account['permission'], $this->config['login']['permission_blacklist']));
            }
        }

        // Check permission for company package
        if (isset($this->config['login']['permission_package']) && (int)$this->config['login']['permission_package'] === 1) {
            if (isset($user['authorization']['package_id']) && (int)$user['authorization']['package_id'] > 0) {
                $key     = sprintf('package-%s', $user['authorization']['package_id']);
                $package = $this->cacheService->getItem($key);

                // Check and clean account permission base of package access
                if (
                    !empty($package)
                    && isset($package['information']['access'])
                    && !empty($package['information']['access'])
                ) {
                    $account['permission'] = array_intersect(array_values($account['permission']), array_values($package['information']['access']));
                }
            }
        }

        // reset permission
        $account['permission'] = array_values($account['permission']);

        // Set source roles params
        // ToDo: Make this part secure
        /* if (isset($params['source']) && !empty($params['source']) && is_string($params['source']) && !in_array($params['source'], $account['roles'])) {
            $this->roleService->addRoleAccount($account, $params['source']);

            // Set new role
            $account['roles'][] = $params['source'];
            $account['roles']   = array_values($account['roles']);
        } */

        // Add or update user data to cache
        $this->manageUserCache($account, $accessToken, $refreshToken, $multiFactor);

        // Save log
        $this->historyService->logger('login', ['request' => $params, 'account' => $account]);

        return [
            'result' => true,
            'data'   => $account,
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function postLoginError($params): array
    {
        // Get a user account
        $account = $this->getAccount([$this->identityColumn => $params['identity']]);

        // Check account exists
        if (!empty($account)) {
            // Save log
            $this->historyService->logger('failedLogin', ['request' => $params, 'account' => $account]);

            // Save failed attempts
            $result = $this->accountLoginAttempts->incrementFailedAttempts(
                ['type' => 'id', 'user_id' => (int)$account['id'], 'security_stream' => $params['security_stream']]
            );
        } else {
            // Save failed attempts
            $userIp = $params['security_stream']['ip']['data']['client_ip'] ?? '';
            $result = $this->accountLoginAttempts->incrementFailedAttempts(
                ['type' => 'ip', 'user_ip' => $userIp, 'security_stream' => $params['security_stream']]
            );
        }

        // Check an attempt result
        if (!$result['can_try']) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => $this->accountLoginAttempts->getErrorMessage(),
                ],
                'status' => $this->accountLoginAttempts->getStatusCode(),
            ];
        }

        // Set message
        $message = 'Invalid Username or Password';
        if (isset($result['attempts_remind']) && is_numeric($result['attempts_remind']) && !$params['security_stream']['ip']['data']['in_whitelist']) {
            $message = sprintf('Invalid Username or Password, You can try %s more times', $result['attempts_remind']);
        }

        return [
            'result' => false,
            'data'   => [],
            'error'  => [
                'message' => $message,
            ],
            'status' => StatusCodeInterface::STATUS_UNAUTHORIZED,
        ];
    }

    /**
     * @param       $params
     *
     * @return array
     * @throws RandomException
     */
    public function perMobileLogin($params): array
    {
        // Set new password as OTP
        $otpCode   = random_int(100000, 999999);
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
                    'first_name' => $params['first_name'] ?? null,
                    'last_name'  => $params['last_name'] ?? null,
                    'source'     => $params['source'] ?? null,
                    'otp'        => $otpCode,
                ]
            );

            // Set is new
            $isNew = 1;
        } else {
            $otp = $this->generatePassword((string)$otpCode);
            $this->accountRepository->updateAccount((int)$account['id'], ['otp' => $otp]);
        }

        // Set otp data
        $otp = [
            'code'        => $otpCode,
            'time_expire' => $otpExpire,
            'method'      => 'sms',
            'type'        => 'login',
        ];

        // Add or update user data to cache
        $this->manageUserCache($account, [], [], [], $otp);

        // Set sms message
        $message = $this->config['otp_sms']['message'] ?? 'Code: %s';
        if (
            isset($params['source'])
            && !empty($params['source'])
            && isset($this->config['otp_sms']['source'])
            && in_array($params['source'], array_keys($this->config['otp_sms']['source']))
        ) {
            $message = $this->config['otp_sms'][$params['source']];
        }

        // Send notification
        $this->notificationService->send(
            [
                'sms' => [
                    'message' => sprintf($message, $otpCode),
                    'mobile'  => $account['mobile'],
                    'source'  => $params['source'] ?? '',
                ],
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

    /**
     * @param       $params
     *
     * @return array
     * @throws RandomException
     */
    public function preMailLogin($params): array
    {
        // Set new password as OTP
        $otpCode   = random_int(100000, 999999);
        $otpExpire = (time() + 180);
        $isNew     = 0;

        // Check account exist
        $account = $this->getAccount(['email' => $params['email']]);

        // Create account if not exist
        // Update OTP password if account exist
        if (empty($account)) {
            $account = $this->addAccount(
                [
                    'email'      => $params['email'],
                    'source'     => $params['source'] ?? null,
                    'first_name' => $params['first_name'] ?? null,
                    'last_name'  => $params['last_name'] ?? null,
                    'otp'        => $otpCode,
                ]
            );

            // Set is new
            $isNew = 1;
        } else {
            $otp = $this->generatePassword((string)$otpCode);
            $this->accountRepository->updateAccount((int)$account['id'], ['otp' => $otp]);
        }

        // Set otp data
        $otp = [
            'code'        => $otpCode,
            'time_expire' => $otpExpire,
            'method'      => 'email',
            'type'        => 'login',
        ];

        // Add or update user data to cache
        $this->manageUserCache($account, [], [], [], $otp);

        // Send notification
        $this->notificationService->send(
            [
                'mail' => [
                    'to'      => [
                        'email' => $account['email'],
                        'name'  => $account['name'],
                    ],
                    'subject' => $this->config['otp_email']['subject'],
                    'body'    => sprintf($this->config['otp_email']['body'], $otpCode),
                ],
            ]
        );

        // Set result
        return [
            'result' => true,
            'data'   => [
                'message'    => 'Verify code send to your email !',
                'name'       => $account['name'],
                'email'      => $account['email'],
                'is_new'     => $isNew,
                'otp_expire' => $otpExpire,
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function logout($params): array
    {
        // Set message
        $message = 'You are logout successfully from this session !';

        // Get and check user
        $user = $this->cacheService->getUser($params['user_id']);
        if (!empty($user)) {
            // Save log
            $this->historyService->logger('logout', ['request' => $params, 'account' => $user['account']]);

            // Check and clean user cache for logout
            if (isset($params['all_session']) && (int)$params['all_session'] === 1) {
                $this->cacheService->deleteUserItem($params['user_id'], 'all_keys', '');
                $message = 'You are logout successfully from all of your sessions !';
            } else {
                $this->cacheService->deleteUserItem($params['user_id'], 'access_keys', $params['token_id']);
                $this->cacheService->deleteUserItem($params['user_id'], 'multi_factor', $params['token_id']);
            }
        }

        return [
            'result' => true,
            'data'   => [
                'message' => $message,
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function addAccount($params, array $operator = []): array
    {
        // Set account name
        $params['name']        = $this->setAccountName($params);
        $params['ip_register'] = $this->utilityService->getClientIp();

        // Set credential
        $credential = null;
        if (isset($params['credential']) && !empty($params['credential'])) {
            $credential = $this->generatePassword($params['credential']);
        }

        // Set otp
        $otp = null;
        if (isset($params['otp']) && !empty($params['otp'])) {
            $otp = $this->generatePassword((string)$params['otp']);
        }

        // Normalize Email
        $email = $params['email'] ?? null;
        $email = $this->normalizeEmail($email);

        // Set add account params
        $paramsAccount = [
            'name'         => $params['name'] ?? null,
            'email'        => $email,
            'identity'     => $params['identity'] ?? null,
            'mobile'       => $params['mobile'] ?? null,
            'credential'   => $credential,
            'otp'          => $otp,
            'status'       => $this->userRegisterStatus(),
            'time_created' => time(),
        ];

        // add account
        $account = $this->accountRepository->addAccount($paramsAccount);
        $account = $this->canonizeAccount($account);

        // Clean up information data
        $profileParams     = ['user_id' => (int)$account['id']];
        $informationParams = [];
        foreach ($params as $key => $value) {
            if (in_array($key, $this->profileFields)) {
                if (empty($value)) {
                    $profileParams[$key] = null;
                } else {
                    $profileParams[$key] = $value;
                }
            }

            if (in_array($key, $this->informationFields)) {
                if (empty($value)) {
                    $informationParams[$key] = null;
                } else {
                    $informationParams[$key] = $value;
                }
            }
        }

        // Set information
        $profileParams['information'] = json_encode(
            $informationParams,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
        );

        // add profile
        $profile = $this->accountRepository->addProfile($profileParams);
        $profile = $this->canonizeProfile($profile);

        // merge account and profile data
        $account = array_merge($account, $profile);

        // Save log
        $this->historyService->logger('register', ['request' => $params, 'account' => $account, 'operator' => $operator]);

        // Send notification
        $this->notificationService->send(
            [
                'information' => [
                    'sender_id'   => (int)$operator['id'] ?? 0,
                    'receiver_id' => (int)$account['id'],
                    'type'        => 'info',
                    'title'       => $this->translatorService->translate('add-account'),
                    'body'        => $this->translatorService->translate('add-account-message'),
                    'source'      => [
                        'module'  => 'user',
                        'section' => 'account',
                        'item'    => (int)$account['id'],
                    ],
                ],
            ]
        );

        // Set user roles
        $this->roleService->addDefaultRoles($account, $operator);

        // Get roles
        $roles = $this->roleService->getRoleAccount((int)$account['id']);

        // Set source roles params
        if (isset($params['source']) && !empty($params['source']) && is_string($params['source']) && !in_array($params['source'], $roles)) {
            $this->roleService->addRoleAccount($account, $params['source']);
        }

        return $account;
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccount($params): array
    {
        $account = $this->accountRepository->getAccount($params);
        return $this->canonizeAccount($account);
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getProfile($params): array
    {
        $profile = $this->accountRepository->getProfile($params);
        return $this->canonizeProfile($profile);
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccountProfile($params): array
    {
        return $this->canonizeAccountProfile($this->accountRepository->getAccountProfile($params));
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccountList($params): array
    {
        $limit  = $params['limit'] ?? 10;
        $page   = $params['page'] ?? 1;
        $order  = $params['order'] ?? ['time_created DESC', 'id DESC'];
        $offset = ((int)$page - 1) * (int)$limit;

        // Set params
        $listParams = [
            'page'   => (int)$page,
            'limit'  => (int)$limit,
            'order'  => $order,
            'offset' => $offset,
        ];

        if (isset($params['name']) && !empty($params['name'])) {
            $listParams['name'] = $params['name'];
        }
        if (isset($params['identity']) && !empty($params['identity'])) {
            $listParams['identity'] = $params['identity'];
        }
        if (isset($params['email']) && !empty($params['email'])) {
            $listParams['email'] = $params['email'];
        }
        if (isset($params['mobile']) && !empty($params['mobile'])) {
            $listParams['mobile'] = $params['mobile'];
        }
        if (isset($params['mobiles']) && !empty($params['mobiles'])) {
            $listParams['mobiles'] = $params['mobiles'];
        }
        if (isset($params['status']) && in_array($params['status'], [0, 1])) {
            $listParams['status'] = $params['status'];
        }
        if (isset($params['status'])) {
            $listParams['status'] = $params['status'];
        }
        if (isset($params['data_from']) && !empty($params['data_from'])) {
            $listParams['data_from'] = strtotime(
                ($params['data_from']) != null
                    ? sprintf('%s 00:00:00', $params['data_from'])
                    : sprintf('%s 00:00:00', date('Y-m-d', strtotime('-1 month')))
            );
        }
        if (isset($params['data_to']) && !empty($params['data_to'])) {
            $listParams['data_to'] = strtotime(
                ($params['data_to']) != null
                    ? sprintf('%s 00:00:00', $params['data_to'])
                    : sprintf('%s 23:59:59', date('Y-m-d'))
            );
        }

        // Set filters
        $filters = $this->prepareFilter($params);
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $itemIdList = [];
                $rowSet     = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $itemIdList[] = $this->canonizeAccountId($row);
                }
                $listParams['id'] = $itemIdList;
            }
        }

        // Merge user id if set
        if (isset($params['id']) && !empty($params['id'])) {
            if (isset($listParams['id'])) {
                $listParams['id'] = array_intersect($listParams['id'], $params['id']);;
            } else {
                $listParams['id'] = $params['id'];
            }
        }

        // Get list
        $list   = [];
        $rowSet = $this->accountRepository->getAccountList($listParams);
        foreach ($rowSet as $row) {
            $list[$row->getId()] = $this->canonizeAccount($row);
        }

        // Get count
        $count = $this->accountRepository->getAccountCount($listParams);

        // Get roles
        $roleList = $this->roleService->getRoleAccountList(array_keys($list));
        foreach ($list as $id => $user) {
            $list[$id]['roles'] = isset($roleList[$user['id']]) ? $roleList[$user['id']] : $this->emptyRoles;
        }

        return [
            'list'      => array_values($list),
            'roles'     => $roleList,
            'paginator' => [
                'count' => $count,
                'limit' => $limit,
                'page'  => $page,
            ],
        ];
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccountListLight($params): array
    {
        // Set params
        $listParams = [];
        if (isset($params['name']) && !empty($params['name'])) {
            $listParams['name'] = $params['name'];
        }
        if (isset($params['identity']) && !empty($params['identity'])) {
            $listParams['identity'] = $params['identity'];
        }
        if (isset($params['email']) && !empty($params['email'])) {
            $listParams['email'] = $params['email'];
        }
        if (isset($params['mobile']) && !empty($params['mobile'])) {
            $listParams['mobile'] = $params['mobile'];
        }
        if (isset($params['mobiles']) && !empty($params['mobiles'])) {
            $listParams['mobiles'] = $params['mobiles'];
        }
        if (isset($params['status']) && in_array($params['status'], [0, 1])) {
            $listParams['status'] = $params['status'];
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $listParams['id'] = $params['id'];
        }

        // Get list
        $list   = [];
        $rowSet = $this->accountRepository->getAccountList($listParams);
        foreach ($rowSet as $row) {
            $list[$row->getId()] = $this->canonizeAccount($row);
        }

        return $list;
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccountProfileList($params): array
    {
        $limit  = $params['limit'] ?? 10;
        $page   = $params['page'] ?? 1;
        $order  = $params['order'] ?? ['time_created DESC', 'id DESC'];
        $offset = ((int)$page - 1) * (int)$limit;

        // Set params
        $listParams = [
            'page'   => (int)$page,
            'limit'  => (int)$limit,
            'order'  => $order,
            'offset' => $offset,
        ];

        if (isset($params['name']) && !empty($params['name'])) {
            $listParams['name'] = $params['name'];
        }
        if (isset($params['identity']) && !empty($params['identity'])) {
            $listParams['identity'] = $params['identity'];
        }
        if (isset($params['email']) && !empty($params['email'])) {
            $listParams['email'] = $params['email'];
        }
        if (isset($params['mobile']) && !empty($params['mobile'])) {
            $listParams['mobile'] = $params['mobile'];
        }
        if (isset($params['mobiles']) && !empty($params['mobiles'])) {
            $listParams['mobiles'] = $params['mobiles'];
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $listParams['id'] = $params['id'];
        }
        if (isset($params['status']) && in_array($params['status'], [0, 1])) {
            $listParams['status'] = $params['status'];
        }
        if (isset($params['data_from']) && !empty($params['data_from'])) {
            $listParams['data_from'] = strtotime(
                ($params['data_from']) != null
                    ? sprintf('%s 00:00:00', $params['data_from'])
                    : sprintf('%s 00:00:00', date('Y-m-d', strtotime('-1 month')))
            );
        }
        if (isset($params['data_to']) && !empty($params['data_to'])) {
            $listParams['data_to'] = strtotime(
                ($params['data_to']) != null
                    ? sprintf('%s 00:00:00', $params['data_to'])
                    : sprintf('%s 23:59:59', date('Y-m-d'))
            );
        }

        $filters = $this->prepareFilter($params);
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $itemIdList = [];
                $rowSet     = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $itemIdList[] = $this->canonizeAccountId($row);
                }
                $listParams['id'] = $itemIdList;
            }
        }

        // Get list
        $list   = [];
        $rowSet = $this->accountRepository->getAccountProfileList($listParams);
        foreach ($rowSet as $row) {
            $list[$row->getId()] = $this->canonizeAccountProfile($row);
        }

        // Get count
        $count = $this->accountRepository->getAccountCount($listParams);

        // Get roles
        $roleList = $this->roleService->getRoleAccountList(array_keys($list));
        foreach ($list as $id => $user) {
            $list[$id]['roles'] = isset($roleList[$user['id']]) ? $roleList[$user['id']] : $this->emptyRoles;
        }

        return [
            'list'      => array_values($list),
            'paginator' => [
                'count' => $count,
                'limit' => (int)$limit,
                'page'  => (int)$page,
            ],
        ];
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function getUserFromCache($id): array
    {
        $user = $this->cacheService->getUser($id);

        return [
            'account' => $user['account'],
            'roles'   => $user['roles'],
        ];
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function getUserFromCacheFull($id): array
    {
        return $this->cacheService->getUser($id);
    }

    /**
     * @param array $params
     *
     * @return int
     */
    public function getAccountCount(array $params = []): int
    {
        return $this->accountRepository->getAccountCount($params);
    }

    /**
     * @param       $params
     * @param       $account
     * @param array $operator
     *
     * @return array
     */
    public function updateAccount($params, $account, array $operator = []): array
    {
        // Set account name
        $params['name'] = $this->setAccountName($params);

        // Clean up information data
        $accountParams     = [];
        $profileParams     = [];
        $informationParams = [];
        foreach ($params as $key => $value) {
            if (in_array($key, $this->accountFields)) {
                if (!empty($value)) {
                    $accountParams[$key] = $value;
                }
            }

            if (in_array($key, $this->profileFields)) {
                if (empty($value)) {
                    $profileParams[$key] = null;
                } else {
                    $profileParams[$key] = $value;
                }
            }

            if (in_array($key, $this->informationFields)) {
                if (empty($value)) {
                    $informationParams[$key] = '';
                } else {
                    $informationParams[$key] = $value;
                }
            }
        }

        // Check information and set data
        if (!empty($informationParams)) {
            $profile = $this->getProfile(['user_id' => (int)$account['id']]);
            foreach ($profile['information'] as $key => $value) {
                if (isset($informationParams[$key]) && empty($informationParams[$key])) {
                    $informationParams[$key] = null;
                } elseif (!isset($informationParams[$key])) {
                    $informationParams[$key] = $value;
                }
            }

            // Set information
            $profileParams['information'] = json_encode(
                $informationParams,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
            );
        }

        // Update account if data set
        if (!empty($accountParams)) {
            $this->accountRepository->updateAccount((int)$account['id'], $accountParams);
        }

        // Update profile if data set
        if (!empty($profileParams)) {
            $this->accountRepository->updateProfile((int)$account['id'], $profileParams);
        }

        // Get account after update
        $account = $this->getAccount(['id' => (int)$account['id']]);
        $profile = $this->getProfile(['user_id' => (int)$account['id']]);
        $account = array_merge($account, $profile);

        // Get user from cache if exist
        $user = $this->cacheService->getUser($account['id']);

        // Set company data and Get company details if company module loaded
        $account['company_id']    = $user['authorization']['company_id'] ?? 0;
        $account['company_title'] = $user['authorization']['company']['title'] ?? '';
        if ($this->hasCompanyService()) {
            $company = $this->companyService->getCompanyDetails((int)$account['id']);
            if (!empty($company)) {
                $account['company_id']    = $company['company_id'];
                $account['company_title'] = $company['company_title'];
            }
        }

        // Add or update user data to cache
        $this->manageUserCache($account);

        // Save log
        $this->historyService->logger('update', ['request' => $params, 'account' => $account, 'operator' => $operator]);

        // Send notification
        $this->notificationService->send(
            [
                'information' => [
                    'sender_id'   => (int)$operator['id'],
                    'receiver_id' => (int)$params['user_id'],
                    'type'        => 'info',
                    'title'       => $this->translatorService->translate('update-account'),
                    'body'        => $this->translatorService->translate('update-account-message'),
                    'source'      => [
                        'module'  => 'user',
                        'section' => 'account',
                        'item'    => (int)$params['user_id'],
                    ],
                ],
            ]
        );

        return $account;
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function addOrGetAccount($params): array
    {
        $account = [];
        if (isset($params['email']) && !empty($params['email'])) {
            $account = $this->getAccount(['email' => $params['email']]);
        } elseif (isset($params['mobile']) && !empty($params['mobile'])) {
            $account = $this->getAccount(['mobile' => $params['mobile']]);
        } elseif (isset($params['identity']) && !empty($params['identity'])) {
            $account = $this->getAccount(['identity' => $params['identity']]);
        } elseif (isset($params['user_id']) && !empty($params['user_id'])) {
            $account = $this->getAccount(['id' => $params['user_id']]);
        }

        if (empty($account)) {
            $addParams = [
                'email'           => $params['email'] ?? null,
                'mobile'          => $params['mobile'] ?? null,
                'identity'        => $params['identity'] ?? null,
                'credential'      => $params['credential'] ?? null,
                'source'          => $params['source'] ?? null,
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
                'item_id'         => $params['item_id'] ?? 0,
                'country'         => $params['country'] ?? null,
                'state'           => $params['state'] ?? null,
                'city'            => $params['city'] ?? null,
                'zip_code'        => $params['zip_code'] ?? null,
            ];

            $account = $this->addAccount($addParams);
        } else {
            $profile = $this->getProfile(['user_id' => (int)$account['id']]);
            $account = array_merge($account, $profile);
        }

        return $account;
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function addOrUpdateAccount($params): array
    {
        $account = [];
        if (isset($params['email']) && !empty($params['email'])) {
            $account = $this->getAccount(['email' => $params['email']]);
        } elseif (isset($params['mobile']) && !empty($params['mobile'])) {
            $account = $this->getAccount(['mobile' => $params['mobile']]);
        } elseif (isset($params['identity']) && !empty($params['identity'])) {
            $account = $this->getAccount(['identity' => $params['identity']]);
        } elseif (isset($params['user_id']) && !empty($params['user_id'])) {
            $account = $this->getAccount(['id' => $params['user_id']]);
        }

        if (empty($account)) {
            $addParams = [
                'email'           => $params['email'] ?? null,
                'mobile'          => $params['mobile'] ?? null,
                'identity'        => $params['identity'] ?? null,
                'credential'      => $params['credential'] ?? null,
                'source'          => $params['source'] ?? null,
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
                'item_id'         => $params['item_id'] ?? 0,
                'country'         => $params['country'] ?? null,
                'state'           => $params['state'] ?? null,
                'city'            => $params['city'] ?? null,
                'zip_code'        => $params['zip_code'] ?? null,
            ];

            $account = $this->addAccount($addParams);
        } else {
            $account = $this->updateAccount($params, $account);

            // Get roles
            $roles = $this->roleService->getRoleAccount((int)$account['id']);

            // Set new role
            if (isset($params['source']) && !empty($params['source']) && is_string($params['source']) && !in_array($params['source'], $roles)) {
                $this->roleService->addRoleAccount($account, $params['source']);

                // Make user logout after edit role
                $this->logout(['user_id' => (int)$account['id'], 'all_session' => 1]);
            }

            // Delete old role
            if (isset($params['remove_role'])
                && !empty($params['remove_role'])
                && is_string($params['remove_role'])
                && in_array($params['remove_role'], $roles)
            ) {
                $this->roleService->deleteRoleAccount($account, $params['remove_role']);

                // Make user logout after edit role
                $this->logout(['user_id' => (int)$account['id'], 'all_session' => 1]);
            }
        }

        return $account;
    }

    /**
     * @param       $account
     *
     * @return array
     */
    public function viewAccount($account): array
    {
        // Canonize account
        $account = $this->canonizeAccount($account);

        // Get user from cache
        $user = $this->cacheService->getUser((int)$account['id']);

        // Set multi factor
        $multiFactorGlobal = (int)$this->config['multi_factor']['status'] ?? 0;
        $multiFactorStatus = (int)$account['multi_factor_status'] ?? 0;
        $multiFactorMethod = $account['multi_factor_method'] ?? $this->config['multi_factor']['default_method'] ?? null;
        $multiFactorVerify = 0;
        unset($account['multi_factor_status']); // ToDo: check it needed to unset
        unset($account['multi_factor_method']); // ToDo: check it needed to unset

        // Get profile
        $profile = $this->getProfile(['user_id' => (int)$account['id']]);

        // Sync profile and account
        $account = array_merge($account, $profile);

        // Set baseurl
        $account['baseurl'] = $this->config['baseurl'] ?? '';

        // Get roles
        $account['roles']      = $this->roleService->getRoleAccount((int)$account['id']);
        $account['roles_full'] = $this->roleService->canonizeAccountRole($account['roles']);

        // Set company data and Get company details if company module loaded
        $account['company_id']    = $user['authorization']['company_id'] ?? 0;
        $account['company_title'] = $user['authorization']['company']['title'] ?? '';
        if ($this->hasCompanyService()) {
            $company = $this->companyService->getCompanyDetails((int)$account['id']);
            if (!empty($company)) {
                $account['company_id']    = $company['company_id'];
                $account['company_title'] = $company['company_title'];
            }
        }

        // Set extra info
        $account['last_login']          = time();
        $account['last_login_view']     = $this->utilityService->date($account['last_login']);
        $account['access_ip']           = $this->utilityService->getClientIp();
        $account['has_password']        = $this->hasPassword($account['id']);
        $account['multi_factor_global'] = $multiFactorGlobal;
        $account['multi_factor_status'] = $multiFactorStatus;
        $account['multi_factor_method'] = $multiFactorMethod;
        $account['multi_factor_verify'] = $multiFactorVerify;
        $account['access_token']        = '';
        $account['refresh_token']       = '';
        $account['permission']          = [];
        $account['token_payload']       = [
            'iat' => '',
            'exp' => '',
            'ttl' => '',
        ];

        // Set permission
        if (isset($this->config['login']['permission']) && (int)$this->config['login']['permission'] === 1) {
            // Set permission params
            $permissionParams = [
                'section' => $this->config['login']['permission_role'] ?? 'api',
                'role'    => $account['roles'],
            ];

            // Get permission list
            $account['permission'] = $this->permissionService->getPermissionRole($permissionParams);

            // Check and cleanup permission list
            if (
                isset($this->config['login']['permission_blacklist'])
                && !empty($this->config['login']['permission_blacklist'])
            ) {
                $account['permission'] = array_values(array_diff($account['permission'], $this->config['login']['permission_blacklist']));
            }
        }

        // Check permission for company package
        if (isset($this->config['login']['permission_package']) && (int)$this->config['login']['permission_package'] === 1) {
            if (isset($user['authorization']['package_id']) && (int)$user['authorization']['package_id'] > 0) {
                $key     = sprintf('package-%s', $user['authorization']['package_id']);
                $package = $this->cacheService->getItem($key);

                // Check and clean account permission base of package access
                if (
                    !empty($package)
                    && isset($package['information']['access'])
                    && !empty($package['information']['access'])
                ) {
                    $account['permission'] = array_intersect(array_values($account['permission']), array_values($package['information']['access']));
                }
            }
        }

        // reset permission
        $account['permission'] = array_values($account['permission']);

        // Add or update user data to cache
        $this->manageUserCache($account);

        return $account;
    }

    /**
     * @param $userId
     *
     * @return bool
     */
    public function hasPassword($userId): bool
    {
        $hash = $this->accountRepository->getAccountPassword((int)$userId);

        if (empty($hash)) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $password
     *
     * @return string
     */
    public function generatePassword(mixed $password): string
    {
        /* switch ($this->hashPattern) {
            default:
            case'argon2id':
                // Set option for a High-Security ARGON2ID
                $options = [
                    'memory_cost' => 1 << 17, // 131072 KB (128 MB)
                    'time_cost'   => 4,         // 4 iterations (same as default)
                    'threads'     => 2,            // 2 parallel threads
                ];

                // Make a High-Security hash password
                $hash = password_hash($password, PASSWORD_ARGON2ID, $options);
                break;

            case'bcrypt':
                $hash = password_hash($password, PASSWORD_BCRYPT);
                break;

            case'sha512':
                $hash = hash('sha512', $password);
                break;
        } */

        // Set option for a High-Security ARGON2ID
        $options = [
            'memory_cost' => 1 << 17, // 131072 KB (128 MB)
            'time_cost'   => 4,         // 4 iterations (same as default)
            'threads'     => 2,            // 2 parallel threads
        ];

        // Make a High-Security hash password
        return password_hash($password, PASSWORD_ARGON2ID, $options);
    }

    /**
     * @param mixed $credential
     * @param mixed $hash
     *
     * @return boolean
     */
    public function passwordEqualityCheck(mixed $credential, mixed $hash): bool
    {
        /* switch ($this->hashPattern) {
            default:
            case'argon2id':
            case'bcrypt':
                $result = password_verify($credential, $hash);
                break;

            case'sha512':
                $result = hash_equals($hash, hash('sha512', $credential));
                break;
        } */

        return password_verify($credential, $hash);
    }

    /**
     * @return int
     */
    public function userRegisterStatus(): int
    {
        //return (int)$this->config['register']['status'] ?? 1;
        return 1;
    }

    /**
     * @param       $params
     * @param       $account
     * @param array $operator
     *
     * @return void
     */
    public function addRoleAccountByAdmin($params, $account, array $operator = []): void
    {
        // Set user roles that receive from service
        if (isset($params['roles'])) {
            //$roles = explode(',', $params['roles']);
            foreach ($params['roles'] as $role) {
                if ($role != 'member') {
                    $this->roleService->addRoleAccount($account, $role, $role == 'admin' ? 'admin' : 'api', $operator);
                }
            }

            // Make user logout after edit role
            $this->logout(['user_id' => (int)$account['id'], 'all_session' => 1]);

            // Send notification
            $this->notificationService->send(
                [
                    'information' => [
                        'sender_id'   => (int)$operator['id'],
                        'receiver_id' => (int)$params['user_id'],
                        'type'        => 'info',
                        'title'       => $this->translatorService->translate('update-roles'),
                        'body'        => $this->translatorService->translate('update-roles-message'),
                        'source'      => [
                            'module'  => 'user',
                            'section' => 'account',
                            'item'    => (int)$params['user_id'],
                        ],
                    ],
                ]
            );
        }
    }

    /**
     * @param        $roles
     * @param        $account
     * @param string $section
     * @param array  $operator
     *
     * @return void
     */
    public function updateAccountRoles($roles, $account, string $section = 'api', array $operator = []): void
    {
        $this->roleService->updateAccountRoles($roles, $account, $section, $operator);

        // Make user logout after edit role
        $this->logout(['user_id' => (int)$account['id'], 'all_session' => 1]);
    }

    /**
     * @param        $roles
     * @param        $account
     * @param array  $operator
     *
     * @return void
     */
    public function updateAccountRolesByAdmin($roles, $account, array $operator = []): void
    {
        $this->roleService->updateAccountRolesByAdmin($roles, $account, $operator);

        // Make user logout after edit role
        $this->logout(['user_id' => (int)$account['id'], 'all_session' => 1]);
    }

    /**
     * @param       $params
     * @param       $account
     *
     * @return void
     */
    public function updatedDeviceToken($params, $account): void
    {
        // Update cache
        $this->cacheService->setUserItem($account['id'], 'device_tokens', $params['device_token']);

        // Save log
        $this->historyService->logger('updatedDeviceToken', ['request' => $params, 'account' => $account]);
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function prepareFilter($params): array
    {
        // Set filter list
        $filters = [];
        foreach ($params as $key => $value) {
            if ($key == 'roles') {
                if (($value != '') && !empty($value) && ($value != null)) {
                    $filters[$key] = [
                        'role'  => $key,
                        'value' => explode(',', $value),
                        'type'  => 'string',
                    ];
                }
            }
        }
        return $filters;
    }

    /**
     * @param       $params
     * @param array $account
     *
     * @return array
     */
    public function addPassword($params, array $account = []): array
    {
        // Set user id
        $userId = $account['id'] ?? $params['user_id'];

        // Generate password
        $credential = $this->generatePassword($params['credential']);

        // Update account
        $this->accountRepository->updateAccount((int)$userId, ['credential' => $credential]);

        // Save log
        $account = empty($account) ? ['id' => $userId] : $account;
        $this->historyService->logger('addPassword', ['request' => $params, 'account' => $account]);

        return [
            'result' => true,
            'data'   => [
                'message' => 'Password set successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param       $account
     * @param array $operator
     *
     * @return array
     */
    public function updatePassword($params, $account, array $operator = []): array
    {
        $hash = $this->accountRepository->getAccountPassword((int)$account['id']);
        if ($this->passwordEqualityCheck($params['current_credential'], $hash)) {
            $credential = $this->generatePassword($params['new_credential']);
            $this->accountRepository->updateAccount((int)$account['id'], ['credential' => $credential]);

            // Save log
            $this->historyService->logger('updatePassword', ['request' => $params, 'account' => $account, 'operator' => $operator]);

            $result = [
                'result' => true,
                'data'   => [
                    'message' => 'Password update successfully !',
                ],
                'error'  => [],
            ];
        } else {
            // Save log
            $this->historyService->logger('failedUpdatePassword', ['request' => $params, 'account' => $account, 'operator' => $operator]);

            $result = [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error to update password !',
                ],
                'status' => StatusCodeInterface::STATUS_UNAUTHORIZED,
            ];
        }

        return $result;
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function updatePasswordByAdmin($params, array $operator = []): array
    {
        // Set password
        $credential = $this->generatePassword($params['credential']);

        // Update user
        $this->accountRepository->updateAccount((int)$params['user_id'], ['credential' => $credential]);

        // Save log
        $this->historyService->logger(
            'updatePasswordByAdmin',
            ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
        );

        // Send notification
        $this->notificationService->send(
            [
                'information' => [
                    'sender_id'   => (int)$operator['id'],
                    'receiver_id' => (int)$params['user_id'],
                    'type'        => 'info',
                    'title'       => $this->translatorService->translate('update-password'),
                    'body'        => $this->translatorService->translate('update-password-message'),
                    'source'      => [
                        'module'  => 'user',
                        'section' => 'account',
                        'item'    => (int)$params['user_id'],
                    ],
                ],
            ]
        );

        return [
            'result' => true,
            'data'   => [
                'message' => 'Password set successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function updateStatusByAdmin($params, array $operator = []): array
    {
        // Set status
        $params['status'] = $params['status'] ?? 0;

        // Set params
        $paramsList = [
            'status'                                               => $params['status'],
            $params['status'] ? 'time_activated' : 'time_disabled' => time(),
        ];

        // Update user
        $this->accountRepository->updateAccount((int)$params['user_id'], $paramsList);

        // Delete cache
        if ($params['status'] == 0) {
            $this->cacheService->deleteUser((int)$params['user_id']);
        }

        // Save log
        $this->historyService->logger(
            'updateStatusByAdmin',
            ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
        );

        // Send notification
        $this->notificationService->send(
            [
                'information' => [
                    'sender_id'   => (int)$operator['id'],
                    'receiver_id' => (int)$params['user_id'],
                    'type'        => 'info',
                    'title'       => $this->translatorService->translate('update-status'),
                    'body'        => $this->translatorService->translate('update-status-message'),
                    'source'      => [
                        'module'  => 'user',
                        'section' => 'account',
                        'item'    => (int)$params['user_id'],
                    ],
                ],
            ]
        );

        return [
            'result' => true,
            'data'   => [
                'message' => 'Status change successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function deleteUserByAdmin($params, array $operator = []): array
    {
        // Delete user
        $this->accountRepository->updateAccount((int)$params['user_id'], ['status' => 0, 'time_deleted' => time()]);

        // Delete cache
        $this->cacheService->deleteUser((int)$params['user_id']);

        // Save log
        $this->historyService->logger(
            'deleteUserByAdmin',
            ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
        );

        // Send notification
        $this->notificationService->send(
            [
                'information' => [
                    'sender_id'   => (int)$operator['id'],
                    'receiver_id' => (int)$params['user_id'],
                    'type'        => 'info',
                    'title'       => $this->translatorService->translate('delete-account'),
                    'body'        => $this->translatorService->translate('delete-account-message'),
                    'source'      => [
                        'module'  => 'user',
                        'section' => 'account',
                        'item'    => (int)$params['user_id'],
                    ],
                ],
            ]
        );

        return [
            'result' => true,
            'data'   => [
                'message' => 'Delete user successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param $account
     * @param $tokenId
     * @param $params
     *
     * @return array
     * @throws RandomException
     */
    public function refreshToken($account, $tokenId, $params): array
    {
        // Get user
        $user = $this->cacheService->getUser($account['id']);

        // Generate new token
        $accessToken = $this->tokenService->encryptToken(
            [
                'account' => $account,
                'type'    => 'access',
                'id'      => $tokenId,
                'origin'  => $params['security_stream']['origin']['data']['origin'] ?? 'public',
                'ip'      => $params['security_stream']['ip']['data']['client_ip'] ?? '',
                'aud'     => $params['security_stream']['url']['data']['client_url'] ?? '',
            ]
        );

        // Set access token in cache
        if (isset($user['access_keys'][$tokenId]) && !empty($user['access_keys'][$tokenId])) {
            $user['access_keys'][$tokenId]['create'] = $accessToken['payload']['iat'];
            $user['access_keys'][$tokenId]['expire'] = $accessToken['payload']['exp'];
        }

        // Set multi factor in cache
        if (isset($user['multi_factor'][$tokenId]) && !empty($user['multi_factor'][$tokenId])) {
            $user['multi_factor'][$tokenId]['create'] = $accessToken['payload']['iat'];
            $user['multi_factor'][$tokenId]['expire'] = $accessToken['payload']['exp'];
        }

        // Update cache
        $this->cacheService->setUserItem($account['id'], 'access_keys', $user['access_keys']);
        $this->cacheService->setUserItem($account['id'], 'multi_factor', $user['multi_factor']);

        // Set result array
        return [
            'result' => true,
            'data'   => [
                'access_token'  => $accessToken['token'],
                'token_payload' => [
                    'iat' => $accessToken['payload']['iat'],
                    'exp' => $accessToken['payload']['exp'],
                    'ttl' => $accessToken['ttl'],
                ],
            ],
            'error'  => [],
        ];
    }

    /**
     * @param     $type
     * @param     $value
     * @param int $id
     *
     * @return bool
     */
    public function isDuplicated($type, $value, int $id = 0): bool
    {
        return (bool)$this->accountRepository->duplicatedAccount(
            [
                'field' => $type,
                'value' => $value,
                'id'    => $id,
            ]
        );
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return void
     */
    public function resetAccount($params, array $operator = []): void
    {
        $account = $this->getAccount(['id' => (int)$params['user_id']]);

        switch ($params['type']) {
            case 'password':
                // Update account
                $this->accountRepository->updateAccount((int)$params['user_id'], ['credential' => null]);

                // Save log
                $this->historyService->logger(
                    'resetPasswordByOperator',
                    [
                        'request'  => $params,
                        'account'  => $account,
                        'operator' => $operator,
                    ]
                );
                break;

            case 'mfa':
                // Update account
                $this->accountRepository->updateAccount(
                    (int)$params['user_id'],
                    [
                        'multi_factor_status' => 0,
                        'multi_factor_method' => null,
                        'multi_factor_secret' => null,
                    ]
                );

                // Save log
                $this->historyService->logger(
                    'resetMfaByOperator',
                    [
                        'request'  => $params,
                        'account'  => $account,
                        'operator' => $operator,
                    ]
                );
                break;

            case 'avatar':
                // Set avatar params
                $avatar = [
                    'avatar'        => '',
                    'avatar_params' => [],
                ];

                // Set account
                $account = ['id' => (int)$params['user_id']];

                // Update profile
                $this->updateAccount($avatar, $account, $operator);

                // Save log
                $this->historyService->logger(
                    'resetAvatarByOperator',
                    [
                        'request'  => $params,
                        'account'  => $account,
                        'operator' => $operator,
                    ]
                );
                break;
        }

        // Make user logout after edit role
        $this->logout(['user_id' => (int)$params['user_id'], 'all_session' => 1]);
    }

    /**
     * @param       $params
     *
     * @return string|null
     */
    public function setAccountName($params): string|null
    {
        if (
            isset($params['first_name'])
            && !empty($params['first_name'])
            && isset($params['last_name'])
            && !empty($params['last_name'])
        ) {
            return sprintf('%s %s', $params['first_name'], $params['last_name']);
        }

        return $params['name'] ?? null;
    }

    /**
     * @param string|null $email
     *
     * @return string|null
     */
    public function normalizeEmail(?string $email): ?string
    {
        $email = trim((string)$email);
        if ($email === '') {
            return null;
        }

        $filter = new StringToLower(['encoding' => 'UTF-8']);
        return $filter->filter($email);
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccountListByOperator($params): array
    {
        $limit  = $params['limit'] ?? 10;
        $page   = $params['page'] ?? 1;
        $order  = $params['order'] ?? ['time_created DESC', 'id DESC'];
        $offset = ((int)$page - 1) * (int)$limit;

        // Set params
        $listParams = [
            'page'   => (int)$page,
            'limit'  => (int)$limit,
            'order'  => $order,
            'offset' => $offset,
        ];

        if (isset($params['name']) && !empty($params['name'])) {
            $listParams['name'] = $params['name'];
        }
        if (isset($params['identity']) && !empty($params['identity'])) {
            $listParams['identity'] = $params['identity'];
        }
        if (isset($params['email']) && !empty($params['email'])) {
            $listParams['email'] = $params['email'];
        }
        if (isset($params['mobile']) && !empty($params['mobile'])) {
            $listParams['mobile'] = $params['mobile'];
        }
        if (isset($params['status']) && in_array($params['status'], [0, 1])) {
            $listParams['status'] = $params['status'];
        }
        if (isset($params['status'])) {
            $listParams['status'] = $params['status'];
        }
        if (isset($params['data_from']) && !empty($params['data_from'])) {
            $listParams['data_from'] = strtotime(
                ($params['data_from']) != null
                    ? sprintf('%s 00:00:00', $params['data_from'])
                    : sprintf('%s 00:00:00', date('Y-m-d', strtotime('-1 month')))
            );
        }
        if (isset($params['data_to']) && !empty($params['data_to'])) {
            $listParams['data_to'] = strtotime(
                ($params['data_to']) != null
                    ? sprintf('%s 00:00:00', $params['data_to'])
                    : sprintf('%s 23:59:59', date('Y-m-d'))
            );
        }

        $filters = $this->prepareFilter($params);
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $itemIdList = [];
                $rowSet     = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $itemIdList[] = $this->canonizeAccountId($row);
                }
                $listParams['id'] = $itemIdList;
            }
        }

        $notAllow = $this->prepareFilter(['roles' => implode(',', $this->roleService->getAdminRoleList())]);
        if (!empty($notAllow)) {
            foreach ($notAllow as $filter) {
                $notAllowItemIdList = [];
                $rowSet             = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $notAllowItemIdList[] = $this->canonizeAccountId($row);
                }
                $listParams['not_allowed_id'] = $notAllowItemIdList;
            }
        }

        // Get list
        $list   = [];
        $rowSet = $this->accountRepository->getAccountList($listParams);
        foreach ($rowSet as $row) {
            $list[$row->getId()] = $this->canonizeAccount($row);
        }

        // Get count
        $count = $this->accountRepository->getAccountCount($listParams);

        // Get roles
        $roleList = $this->roleService->getRoleAccountList(array_keys($list));
        foreach ($list as $id => $user) {
            $list[$id]['roles'] = isset($roleList[$user['id']]) ? $roleList[$user['id']] : $this->emptyRoles;
        }

        return [
            'list'      => array_values($list),
            'roles'     => $roleList,
            'paginator' => [
                'count' => $count,
                'limit' => $limit,
                'page'  => $page,
            ],
        ];
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getAccountByOperator($params): array
    {
        $notAllow = $this->prepareFilter(['roles' => implode(',', $this->roleService->getAdminRoleList())]);
        if (!empty($notAllow)) {
            foreach ($notAllow as $filter) {
                $notAllowItemIdList = [];
                $rowSet             = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $notAllowItemIdList[] = $this->canonizeAccountId($row);
                }
                $params['not_allowed_id'] = $notAllowItemIdList;
            }
        }
        $account = $this->accountRepository->getAccount($params);
        return $this->canonizeAccount($account);
    }

    /**
     * @param       $params
     *
     * @return array
     */
    public function getProfileByOperator($params): array
    {
        $notAllow = $this->prepareFilter(['roles' => implode(',', $this->roleService->getAdminRoleList())]);
        if (!empty($notAllow)) {
            foreach ($notAllow as $filter) {
                $notAllowItemIdList = [];
                $rowSet             = $this->accountRepository->getIdFromFilter($filter);
                foreach ($rowSet as $row) {
                    $notAllowItemIdList[] = $this->canonizeAccountId($row);
                }
                $params['not_allowed_id'] = $notAllowItemIdList;
            }
        }
        $profile = $this->accountRepository->getProfile($params);
        return $this->canonizeProfile($profile);
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function updateStatusByOperator($params, array $operator = []): array
    {
        // Set status
        $params['status'] = $params['status'] ?? 0;

        // Set params
        $paramsList = [
            'status'                                               => $params['status'],
            $params['status'] ? 'time_activated' : 'time_disabled' => time(),
        ];

        // Update user
        $this->accountRepository->updateAccount((int)$params['user_id'], $paramsList);

        // Delete cache
        if ($params['status'] == 0) {
            $this->cacheService->deleteUser((int)$params['user_id']);
        }

        // Save log
        $this->historyService->logger(
            'updateStatusByOperator',
            ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
        );

        return [
            'result' => true,
            'data'   => [
                'message' => 'Status change successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function deleteUserByOperator($params, array $operator = []): array
    {
        $this->accountRepository->updateAccount((int)$params['user_id'], ['status' => 0, 'time_deleted' => time()]);
        $this->cacheService->deleteUser((int)$params['user_id']);

        // Save log
        $this->historyService->logger(
            'deleteUserByOperator',
            ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
        );

        return [
            'result' => true,
            'data'   => [
                'message' => 'Delete user successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param       $params
     * @param array $operator
     *
     * @return array
     */
    public function updatePasswordByOperator($params, array $operator = []): array
    {
        $credential = $this->generatePassword($params['credential']);

        // Update account
        $this->accountRepository->updateAccount((int)$params['user_id'], ['credential' => $credential]);

        // Save log
        $this->historyService->logger(
            'updatePasswordByOperator',
            ['request' => $params, 'account' => $this->getAccount(['id' => (int)$params['user_id']]), 'operator' => $operator]
        );

        return [
            'result' => true,
            'data'   => [
                'message' => 'Password set successfully !',
            ],
            'error'  => [],
        ];
    }

    /**
     * @param int    $userId
     * @param string $tokenId
     * @param string $ip
     *
     * @return void
     */
    public function updateUserOnline(int $userId, string $tokenId, string $ip): void
    {
        // Fetch current sessions
        $sessions = $this->cacheService->getItem($this->onlineSessionsKey);

        // Update session data for the token
        $sessions[$userId][$tokenId] = [
            'id'               => $userId,
            'ip'               => $ip,
            'token_id'         => $tokenId,
            'last_active'      => time(),
            'last_active_view' => $this->utilityService->date(time()),
        ];

        // Save back to cache
        $this->cacheService->setItem($this->onlineSessionsKey, $sessions);
    }

    /**
     * @param array $params
     * @param array $operator
     *
     * @return array
     */
    public function deleteUserOnline(array $params, array $operator): array
    {
        // Get and check user
        $user = $this->cacheService->getUser($params['user_id']);
        if (!empty($user)) {
            $this->cacheService->deleteUserItem($params['user_id'], 'access_keys', $params['token_id']);
            $this->cacheService->deleteUserItem($params['user_id'], 'multi_factor', $params['token_id']);

            // Save log
            $this->historyService->logger('terminal_session', ['request' => $params, 'account' => $user['account'], 'operator' => $operator]);

            // Set result
            return [
                'result' => true,
                'data'   => [
                    'params'  => $params,
                    'message' => 'User session terminated !',
                ],
                'error'  => [],
            ];
        }

        return [
            'result' => false,
            'data'   => [],
            'error'  => [
                'message' => 'User not found by requested data !',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getUserOnlineList(): array
    {
        // Set cutoff time
        $cutoffTime = time() - $this->onlineTimeout;

        // Get all online session
        $sessions = $this->cacheService->getItem($this->onlineSessionsKey);

        // Set user list params
        $listParams = [
            'limit' => 1000,
            'page'  => 1,
            'id'    => array_keys($sessions),
        ];

        // Get user list
        $userList = $this->getAccountListLight($listParams);

        // Remove expired sessions
        foreach ($sessions as $userId => $tokens) {
            foreach ($tokens as $tokenId => $data) {
                if ($data['last_active'] < $cutoffTime) {
                    unset($sessions[$userId][$tokenId]);
                }
            }

            // If no active tokens remain for a user, remove the user entry
            if (empty($sessions[$userId])) {
                unset($sessions[$userId]);
                unset($userList[$userId]);
            } else {
                // Set session to user list
                $userList[$userId]['sessions_count'] = count($sessions[$userId]);
                $userList[$userId]['sessions']       = array_values($sessions[$userId]);
            }
        }

        // Update cache after cleanup
        $this->cacheService->setItem($this->onlineSessionsKey, $sessions);

        return array_values($userList);
    }

    /**
     * Manage the user data cache by setting or updating it.
     *
     * @param array $account
     * @param array $accessToken
     * @param array $refreshToken
     * @param array $multiFactor
     * @param array $otp
     *
     * @return array
     */
    public function manageUserCache(array $account, array $accessToken = [], array $refreshToken = [], array $multiFactor = [], array $otp = []): array
    {
        // Fetch existing cache if available
        $user        = $this->cacheService->getUser($account['id'] ?? null) ?: [];
        $currentTime = time();

        // Helper function to update and clean tokens
        $mergeTokens = function (array $account, array $existingTokens, array $newToken, string $tokenType) use ($currentTime) {
            // Remove expired tokens
            $existingTokens = array_filter($existingTokens, function ($token) use ($currentTime) {
                return isset($token['expire']) && $token['expire'] > $currentTime;
            });

            // Add or update token with the key as array key
            if (isset($newToken['id'], $newToken['payload']['iat'], $newToken['payload']['exp'])) {
                unset($existingTokens[$newToken['id']]); // Remove existing token with the same key
                $existingTokens[$newToken['id']] = [
                    'id'     => $newToken['id'],
                    'create' => $newToken['payload']['iat'],
                    'expire' => $newToken['payload']['exp'],
                    'origin' => $newToken['payload']['origin'] ?? 'public',
                ];
            }

            // Enforce single session policy if enabled
            if (isset($this->config['login']['session_policy']) && $this->config['login']['session_policy'] === 'single') {
                $existingTokens = isset($newToken['id']) ? [$newToken['id'] => $existingTokens[$newToken['id']]] : [];

                // Log session policy enforcement for access tokens
                if ($tokenType === 'access_keys') {
                    $this->historyService->logger('logout_all', [
                        'request' => [],
                        'account' => $account,
                    ]);
                }
            }

            return $existingTokens;
        };

        // Helper function to update and clean multi-factor data
        $mergeMultiFactor = function (array $existingMultiFactor, array $newMultiFactor) use ($currentTime) {
            // Remove expired tokens
            $existingMultiFactor = array_filter($existingMultiFactor, function ($mfData) use ($currentTime) {
                return isset($mfData['expire']) && $mfData['expire'] > $currentTime;
            });

            // Add or update token with the key as array key
            if (isset($newMultiFactor['id'], $newMultiFactor['expire'])) {
                $existingMultiFactor[$newMultiFactor['id']] = [
                    'id'                  => $newMultiFactor['id'],
                    'multi_factor_global' => $newMultiFactor['multi_factor_global'] ?? null,
                    'multi_factor_status' => $newMultiFactor['multi_factor_status'] ?? null,
                    'multi_factor_method' => $newMultiFactor['multi_factor_method'] ?? null,
                    'multi_factor_verify' => $newMultiFactor['multi_factor_verify'] ?? null,
                    'secret'              => $newMultiFactor['secret'] ?? null,
                    'create'              => $newMultiFactor['create'],
                    'expire'              => $newMultiFactor['expire'],
                ];
            }

            return $existingMultiFactor;
        };

        // Build cache structure with input priority and defaults
        $cacheParams = [
            'account'       => [
                'id'                  => $account['id'] ?? $user['account']['id'] ?? null,
                'name'                => $account['name'] ?? $user['account']['name'] ?? 'Guest',
                'email'               => $account['email'] ?? $user['account']['email'] ?? 'guest@example.com',
                'identity'            => $account['identity'] ?? $user['account']['identity'] ?? null,
                'mobile'              => $account['mobile'] ?? $user['account']['mobile'] ?? null,
                'first_name'          => $account['first_name'] ?? $user['account']['first_name'] ?? 'FirstName',
                'last_name'           => $account['last_name'] ?? $user['account']['last_name'] ?? 'LastName',
                'avatar'              => $account['avatar'] ?? $user['account']['avatar'] ?? null,
                'time_created'        => $account['time_created'] ?? $user['account']['time_created'] ?? $currentTime,
                'last_login'          => $account['last_login'] ?? $user['account']['last_login'] ?? null,
                'status'              => $account['status'] ?? $user['account']['status'] ?? 'active',
                'has_password'        => $account['has_password'] ?? $user['account']['has_password'] ?? $this->hasPassword((int)($account['id'] ?? 0)),
                'multi_factor_global' => $account['multi_factor_global'] ?? $user['account']['multi_factor_global'] ?? null,
                'multi_factor_status' => $account['multi_factor_status'] ?? $user['account']['multi_factor_status'] ?? null,
                'multi_factor_method' => $account['multi_factor_method'] ?? $user['account']['multi_factor_method'] ?? null,
                'multi_factor_verify' => $account['multi_factor_verify'] ?? $user['account']['multi_factor_verify'] ?? null,
                'company_id'          => $account['company_id'] ?? $user['account']['company_id'] ?? 0,
                'company_title'       => $account['company_title'] ?? $user['account']['company_title'] ?? null,
            ],
            'roles'         => $account['roles'] ?? $user['roles'] ?? [],
            'permission'    => $account['permission'] ?? $user['permission'] ?? null,
            'access_keys'   => $mergeTokens($account, $user['access_keys'] ?? [], $accessToken, 'access_keys'),
            'refresh_keys'  => $mergeTokens($account, $user['refresh_keys'] ?? [], $refreshToken, 'refresh_keys'),
            'multi_factor'  => $mergeMultiFactor($user['multi_factor'] ?? [], $multiFactor),
            'otp'           => $otp ?? $user['otp'] ?? [],
            'device_tokens' => $account['device_tokens'] ?? $user['device_tokens'] ?? [],
            'authorization' => $account['authorization'] ?? $user['authorization'] ?? [],
        ];

        // Save the updated cache
        $this->cacheService->setUser($account['id'], $cacheParams);

        return $cacheParams;
    }

    /**
     * @param $account
     *
     * @return array
     */
    public function canonizeAccount($account): array
    {
        if (empty($account)) {
            return [];
        }

        if (is_object($account)) {
            $account = [
                'id'                  => (int)$account->getId(),
                'name'                => $account->getName(),
                'identity'            => $account->getIdentity(),
                'email'               => $account->getEmail(),
                'mobile'              => $account->getMobile(),
                'status'              => (int)$account->getStatus(),
                'time_created'        => $account->getTimeCreated(),
                'multi_factor_status' => (int)$account->getMultiFactorStatus(),
                'multi_factor_method' => $account->getMultiFactorMethod(),
            ];
        } else {
            $account = [
                'id'                  => (int)$account['id'],
                'name'                => $account['name'] ?? '',
                'email'               => $account['email'] ?? '',
                'identity'            => $account['identity'] ?? '',
                'mobile'              => $account['mobile'] ?? '',
                'status'              => (int)$account['status'],
                'time_created'        => $account['time_created'] ?? '',
                'multi_factor_status' => (int)$account['multi_factor_status'],
                'multi_factor_method' => $account['multi_factor_method'],
            ];
        }

        // Set time
        $account['time_created_view'] = ' - ';
        if (!empty($account['time_created']) && is_numeric($account['time_created'])) {
            $account['time_created_view'] = $this->utilityService->date($account['time_created']);
        }

        return $account;
    }

    /**
     * @param $profile
     *
     * @return array
     */
    public function canonizeProfile($profile): array
    {
        if (empty($profile)) {
            return [];
        }

        if (is_object($profile)) {
            $profile = [
                'user_id'     => (int)$profile->getUserId(),
                'first_name'  => $profile->getFirstName(),
                'last_name'   => $profile->getLastName(),
                'birthdate'   => $profile->getBirthdate(),
                'gender'      => $profile->getGender(),
                'avatar'      => $profile->getAvatar(),
                'information' => $profile->getInformation(),
            ];
        } else {
            $profile = [
                'user_id'     => (int)$profile['user_id'],
                'first_name'  => $profile['first_name'],
                'last_name'   => $profile['last_name'],
                'birthdate'   => $profile['birthdate'],
                'gender'      => $profile['gender'],
                'avatar'      => $profile['avatar'],
                'information' => $profile['information'],
            ];
        }

        // Set information
        $profile['information'] = !empty($profile['information']) ? json_decode($profile['information'], true) : [];

        // Set avatar
        //$profile = $this->avatarService->createUri($profile);

        return $profile;
    }

    /**
     * @param object|array $roleAccountList
     *
     * @return int|null
     */
    public function canonizeAccountId(object|array $roleAccountList): int|null
    {
        if (empty($roleAccountList)) {
            return 0;
        }

        if (is_object($roleAccountList)) {
            $accountId = $roleAccountList->getUserId();
        } else {
            $accountId = $roleAccountList['user_id'];
        }

        return $accountId;
    }

    /**
     * @param $account
     *
     * @return array
     */
    public function canonizeAccountProfile($account): array
    {
        if (empty($account)) {
            return [];
        }

        if (is_object($account)) {
            $account = [
                'id'           => (int)$account->getId(),
                'name'         => $account->getName(),
                'identity'     => $account->getIdentity(),
                'email'        => $account->getEmail(),
                'mobile'       => $account->getMobile(),
                'status'       => (int)$account->getStatus(),
                'time_created' => $account->getTimeCreated(),
                'first_name'   => $account->getFirstName(),
                'last_name'    => $account->getLastName(),
                'birthdate'    => $account->getBirthdate(),
                'gender'       => $account->getGender(),
                'avatar'       => $account->getAvatar(),
                'information'  => $account->getInformation(),
            ];
        } else {
            $account = [
                'id'           => (int)$account['id'],
                'name'         => $account['name'] ?? '',
                'email'        => $account['email'] ?? '',
                'identity'     => $account['identity'] ?? '',
                'mobile'       => $account['mobile'] ?? '',
                'status'       => (int)$account['status'],
                'time_created' => $account['time_created'] ?? '',
                'first_name'   => $account['first_name'] ?? '',
                'last_name'    => $account['last_name'] ?? '',
                'birthdate'    => $account['birthdate'] ?? '',
                'gender'       => $account['gender'] ?? '',
                'avatar'       => $account['avatar'] ?? '',
                'information'  => $account['information'] ?? '',
            ];
        }

        // Set information
        $account['information'] = !empty($account['information']) ? json_decode($account['information'], true) : [];

        // Set time
        $account['time_created_view'] = ' - ';
        if (!empty($account['time_created']) && is_numeric($account['time_created'])) {
            $account['time_created_view'] = $this->utilityService->date($account['time_created']);
        }

        // Set avatar
        //$account = $this->avatarService->createUri($account);

        return $account;
    }
}