<?php

namespace User\Validator;

use Laminas\Validator\AbstractValidator;
use User\Service\AccountService;
use User\Service\UtilityService;

use function sprintf;

class PasswordValidator extends AbstractValidator
{
    const TOO_SHORT     = 'stringLengthTooShort';
    const TOO_LONG      = 'stringLengthTooLong';
    const HAS_PASSWORD  = 'hasPassword';
    const WEAK_PASSWORD = 'weakPassword';

    /** @var int */
    protected int $max;

    /** @var int */
    protected int $min;

    /** @var array */
    protected array $messageTemplates = [];

    /** @var array */
    protected $options
        = [
            'min' => 8,
            'max' => 32,
            'check_strong' => 1,
        ];

    /** @var AccountService */
    protected AccountService $accountService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    public function __construct(
        AccountService $accountService,
        UtilityService $utilityService,
        $options = []
    ) {
        $this->accountService = $accountService;
        $this->utilityService = $utilityService;
        $this->options        = array_merge($this->options, $options);

        $this->messageTemplates = [
            self::TOO_SHORT     => sprintf('Password is less than %s characters long', (int)$this->options['min']),
            self::TOO_LONG      => sprintf('Password is more than %s characters long', (int)$this->options['max']),
            self::HAS_PASSWORD  => 'User set password before !',
            self::WEAK_PASSWORD => 'XXX Please enter a stronger password for added security. Ensure it includes uppercase and lowercase letters, a number, and a special character.',
        ];

        parent::__construct();
    }

    public function isValid($value): bool
    {
        $this->setValue($value);

        if (!empty($this->options['max']) && $this->options['max'] < strlen($value)) {
            $this->error(static::TOO_LONG);
            return false;
        }

        if (!empty($this->options['min']) && $this->options['min'] > strlen($value)) {
            $this->error(static::TOO_SHORT);
            return false;
        }

        // Check user has password for set new password
        if (isset($this->options['check_has_password']) && $this->options['check_has_password'] === 1) {
            $hash = $this->accountService->hasPassword($this->options['user_id']);
            if (!empty($hash)) {
                $this->error(static::HAS_PASSWORD);
                return false;
            }
        }

        // Check password is strong
        if (isset($this->options['check_strong']) && $this->options['check_strong'] === 1) {
            $isPasswordStrong = $this->utilityService->isPasswordStrong($value);
            if (!$isPasswordStrong) {
                $this->error(static::WEAK_PASSWORD);
                return false;
            }
        }

        return true;
    }
}
