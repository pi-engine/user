<?php

namespace User\Validator;

use Laminas\Validator\AbstractValidator;
use User\Service\AccountService;
use User\Service\CacheService;
use function array_merge;

class OtpValidator extends AbstractValidator
{
    const TOO_SHORT = 'stringLengthTooShort';
    const TOO_LONG  = 'stringLengthTooLong';
    const NOT_FOUND = 'stringAccountNotFound';
    const NOT_VALID = 'stringOtpNotTrue';

    /** @var int */
    protected int $max;

    /** @var int */
    protected int $min;

    /** @var array */
    protected array $messageTemplates = [];

    /** @var array */
    protected $options
        = [
            'min'    => 6,
            'max'    => 32,
            'mobile' => '',
        ];

    /** @var AccountService */
    protected AccountService $accountService;

    /* @var CacheService */
    protected CacheService $cacheService;

    public function __construct(
        AccountService $accountService,
        CacheService $cacheService,
        $options = []
    ) {
        $this->accountService = $accountService;
        $this->cacheService   = $cacheService;
        $this->options        = array_merge($this->options, $options);

        $this->messageTemplates = [
            self::TOO_SHORT => 'Password is less than %min% characters long',
            self::TOO_LONG  => 'Password is more than %max% characters long',
            self::NOT_FOUND => 'Account by this information was not found !',
            self::NOT_VALID => 'OTP Is not valid !',
        ];

        parent::__construct();
    }

    public function isValid($value): bool
    {
        $this->setValue($value);

        if (!empty($this->options['max']) && $this->options['max'] < strlen($value)) {
            $this->max = (int)$this->options['max'];
            $this->error(static::TOO_LONG);
            return false;
        }

        if (!empty($this->options['min']) && $this->options['min'] > strlen($value)) {
            $this->min = (int)$this->options['min'];
            $this->error(static::TOO_SHORT);
            return false;
        }

        // Get and check account
        if (isset($this->options['mobile']) && !empty($this->options['mobile'])) {
            $account = $this->accountService->getAccount(['mobile' => $this->options['mobile']]);
            if (empty($account) || (int)$account['id'] === 0) {
                $this->error(static::NOT_FOUND);
                return false;
            }
        } elseif (isset($this->options['email']) && !empty($this->options['email'])) {
            $account = $this->accountService->getAccount(['email' => $this->options['email']]);
            if (empty($account) || (int)$account['id'] === 0) {
                $this->error(static::NOT_FOUND);
                return false;
            }
        } else {
            $this->error(static::NOT_FOUND);
            return false;
        }

        // Get user
        $user = $this->cacheService->getUser($account['id']);

        if (
            !isset($user['otp'])
            || empty($user['otp'])
            || $user['otp']['code'] != $value
            || $user['otp']['time_expire'] < time()
        ) {
            $this->error(static::NOT_VALID);
            return false;
        }

        return true;
    }
}
