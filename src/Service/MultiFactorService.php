<?php

declare(strict_types=1);

namespace Pi\User\Service;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\UtilityService;
use Pi\Notification\Service\NotificationService;
use Pi\User\Repository\AccountRepositoryInterface;
use Random\RandomException;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;

class MultiFactorService implements ServiceInterface
{
    /* @var AccountRepositoryInterface */
    protected AccountRepositoryInterface $accountRepository;

    /** @var AccountService */
    protected AccountService $accountService;

    /* @var CacheService */
    protected CacheService $cacheService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /** @var NotificationService */
    protected NotificationService $notificationService;

    /* @var array */
    protected array $config;

    /**
     * @param CacheService   $cacheService
     * @param UtilityService $utilityService
     * @param                $config
     */
    public function __construct(
        AccountRepositoryInterface $accountRepository,
        AccountService             $accountService,
        CacheService               $cacheService,
        UtilityService             $utilityService,
        NotificationService        $notificationService,
                                   $config
    ) {
        $this->accountRepository   = $accountRepository;
        $this->accountService      = $accountService;
        $this->cacheService        = $cacheService;
        $this->utilityService      = $utilityService;
        $this->notificationService = $notificationService;
        $this->config              = $config;
    }

    /**
     * @param $mfa
     * @param $params
     *
     * @return mixed|string
     */
    public function setMethod($mfa, $params): mixed
    {
        $allowedMethods = $this->config['multi_factor']['allowed_method'] ?? [];
        $defaultMethod  = $this->config['multi_factor']['default_method'] ?? 'app';

        if (!empty($params['method']) && in_array($params['method'], $allowedMethods, true)) {
            return $params['method'];
        }

        if (!empty($mfa['multi_factor_method']) && in_array($mfa['multi_factor_method'], $allowedMethods, true)) {
            return $mfa['multi_factor_method'];
        }

        return in_array($defaultMethod, $allowedMethods, true) ? $defaultMethod : 'app';
    }

    /**
     * Secret for app method should be save in database and secret for other methods just save on cache
     *
     * @param $account
     * @param $params
     * @param $tokenId
     *
     * @return array
     * @throws RandomException
     * @throws TwoFactorAuthException
     */
    public function requestMfa($account, $params, $tokenId): array
    {
        $secret = null;
        $image  = null;

        // Get MFA information
        $mfa    = $this->accountRepository->getMultiFactor((int)$account['id']);
        $method = $this->setMethod($mfa, $params);

        // Request and generate code
        switch ($method) {
            case 'app':
                $multiFactorAuth = new TwoFactorAuth(new EndroidQrCodeProvider(), $this->config['sitename'] ?? null);
                $message         = 'Enter the 6-digit verification code from your authenticator app to proceed with login.';

                if (empty($mfa['multi_factor_secret'])) {
                    $secret = $multiFactorAuth->createSecret();
                    $image  = $multiFactorAuth->getQRCodeImageAsDataUri($account['email'], $secret);
                    $message
                            = 'To enable two-factor authentication, scan the QR code with your authenticator app (e.g., Google Authenticator, Authy) and enter the 6-digit code.';
                }
                break;

            case 'sms':
            case 'email':
                $contactField = $method === 'sms' ? 'mobile' : 'email';
                $contactType  = ucfirst($contactField);

                // Check account
                if (empty($account[$contactField])) {
                    return [
                        'result' => false,
                        'data'   => [],
                        'error'  => [
                            'message'      => "No {$contactType} found. Please use another verification method.",
                            'key'          => 'no-contact-type-found-please-use-another-verification-method',
                            'contact_type' => $contactType,
                        ],
                    ];
                }

                // Generate OTP
                $code   = random_int(100000, 999999);
                $secret = $this->accountService->generatePassword((string)$code);

                // Set notification params
                if ($method === 'sms') {
                    $notificationParams = [
                        'mobile'  => $account['mobile'],
                        'message' => sprintf($this->config['multi_factor'][$method]['message'], $code),
                    ];
                } else {
                    $notificationParams = [
                        'to'      => [
                            'email' => $account['email'],
                            'name'  => $account['name'] ?? '',
                        ],
                        'subject' => $this->config['multi_factor']['email']['subject'],
                        'body'    => sprintf($this->config['multi_factor']['email']['body'], $code),
                    ];
                }

                // Send notification
                $this->notificationService->send([$method => $notificationParams]);

                $message
                    = "Your verification code has been sent to your {$contactType}. This code expires in 2 minutes. Verify it on our platform and do not share it with anyone.";
                break;

            default:
                return [
                    'result' => false,
                    'data'   => [],
                    'error'  => [
                        'message' => 'Please select a valid verification method.',
                        'key'     => 'please-select-a-valid-verification-method',
                    ],
                ];
        }

        // Set cache params
        $multiFactor = [
            'id'                  => $tokenId,
            'secret'              => $secret,
            'create'              => time(),
            'expire'              => time() + 120,
            'multi_factor_global' => (int)($this->config['multi_factor']['status'] ?? 0),
            'multi_factor_status' => (int)$mfa['multi_factor_status'],
            'multi_factor_method' => $method,
            'multi_factor_verify' => 0,
        ];

        // Cache multiFactor data
        $this->accountService->manageUserCache($account, [], [], $multiFactor);

        return [
            'result' => true,
            'data'   => [
                'multi_factor_global' => (int)($this->config['multi_factor']['status'] ?? 0),
                'multi_factor_status' => (int)$mfa['multi_factor_status'],
                'multi_factor_method' => $method,
                'multi_factor_verify' => 0,
                'message'             => $message,
                'secret'              => $secret,
                'image'               => $image,
                // remove
                'code'                => $code ?? null,
            ],
            'error'  => [],
        ];
    }

    /**
     * @param $account
     * @param $params
     * @param $tokenData
     *
     * @return array
     * @throws TwoFactorAuthException
     */
    public function verifyMfa($account, $params, $tokenData): array
    {
        // Get from cache
        $user = $this->cacheService->getUser($account['id']);

        // Check mfa request set
        if (!isset($user['multi_factor'][$tokenData['id']]) || empty($user['multi_factor'][$tokenData['id']])) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'No request set for your current login',
                    'key'     => 'no-request-set-for-your-current-login',
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ],
                'status' => StatusCodeInterface::STATUS_FORBIDDEN,
            ];
        }

        // Get MFA information
        $mfa    = $this->accountRepository->getMultiFactor((int)$account['id']);
        $method = $user['multi_factor'][$tokenData['id']]['multi_factor_method'];
        $secret = $user['multi_factor'][$tokenData['id']]['secret'];

        // Check and verify code
        $result = false;
        switch ($method) {
            case 'app':
                // Call MultiFactorAuth
                $multiFactorAuth = new TwoFactorAuth(new EndroidQrCodeProvider(), $this->config['sitename'] ?? null);

                // check secret code and verify code
                if (
                    isset($mfa['multi_factor_status'])
                    && (int)$mfa['multi_factor_status'] === 1
                    && isset($mfa['multi_factor_secret'])
                    && !empty($mfa['multi_factor_secret'])
                ) {
                    $result = $multiFactorAuth->verifyCode($mfa['multi_factor_secret'], $params['verification']);
                } elseif (!empty($secret)) {
                    $result = $multiFactorAuth->verifyCode($secret, $params['verification']);
                }
                break;

            case 'sms':
            case 'email':
                $result = $this->accountService->passwordEqualityCheck($params['verification'], $secret);
                break;

            default:
                return [
                    'result' => false,
                    'data'   => [],
                    'error'  => [
                        'message' => 'Please select a valid verification method.',
                        'key'     => 'please-select-a-valid-verification-method',
                        'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                    ],
                    'status' => StatusCodeInterface::STATUS_FORBIDDEN,
                ];
        }

        // Check a result
        if (!$result) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error to verify code !',
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ],
                'status' => StatusCodeInterface::STATUS_FORBIDDEN,
            ];
        }

        // Set cache params
        $multiFactor = [
            'id'                  => $tokenData['id'],
            'secret'              => null,
            'create'              => $tokenData['iat'],
            'expire'              => $tokenData['exp'],
            'multi_factor_global' => (int)($this->config['multi_factor']['status'] ?? 0),
            'multi_factor_status' => 1,
            'multi_factor_method' => $method,
            'multi_factor_verify' => 1,
        ];

        // Cache multiFactor data
        $this->accountService->manageUserCache($account, [], [], $multiFactor);

        // Check update profile
        if ((int)$mfa['multi_factor_status'] === 0) {
            // Set mfa params
            if ($method == 'app') {
                $updateParams = [
                    'multi_factor_status' => 1,
                    'multi_factor_method' => $method,
                    'multi_factor_secret' => $params['multi_factor_secret'],
                ];
            } else {
                $updateParams = [
                    'multi_factor_status' => 1,
                    'multi_factor_method' => $method,
                ];
            }

            // Update account
            $this->accountService->updateAccount($updateParams, $account);
        }

        return [
            'result' => true,
            'data'   => [
                'message'             => 'Congratulations! Your multi-factor authentication (MFA) has been successfully verified. Your account is now secure.',
                'multi_factor_global' => (int)$this->config['multi_factor']['status'] ?? 0,
                'multi_factor_method' => $method,
                'multi_factor_status' => 1,
                'multi_factor_verify' => 1,
            ],
            'error'  => [],
        ];
    }
}