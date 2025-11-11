<?php

declare(strict_types=1);

namespace Pi\User\Validator;

use Laminas\Validator\AbstractValidator;
use Pi\Core\Service\ConfigService;
use Pi\Core\Service\UtilityService;
use Pi\User\Service\AccountService;
use function sprintf;

class PasswordValidator extends AbstractValidator
{
    const string TOO_SHORT     = 'stringLengthTooShort';
    const string TOO_LONG      = 'stringLengthTooLong';
    const string HAS_PASSWORD  = 'hasPassword';
    const string WEAK_PASSWORD = 'weakPassword';

    /** @var int */
    protected int $max;

    /** @var int */
    protected int $min;

    /** @var array */
    protected array $messageTemplates = [];

    /** @var array */
    protected $options
        = [
            'min'          => 12,
            'max'          => 32,
            'check_strong' => true,
            'check_length' => true,
        ];

    /** @var AccountService */
    protected AccountService $accountService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /** @var ConfigService */
    protected ConfigService $configService;

    public function __construct(
        AccountService $accountService,
        UtilityService $utilityService,
        ConfigService  $configService,
                       $options = []
    ) {
        $this->accountService = $accountService;
        $this->utilityService = $utilityService;
        $this->configService  = $configService;
        $this->options        = array_merge($this->options, $options);

        $this->messageTemplates = [
            self::TOO_SHORT     => sprintf('Password is less than %s characters long', (int)$this->options['min']),
            self::TOO_LONG      => sprintf('Password is more than %s characters long', (int)$this->options['max']),
            self::HAS_PASSWORD  => 'User set password before !',
            self::WEAK_PASSWORD => 'Please enter a stronger password for added security. Ensure it includes uppercase and lowercase letters, a number, and a special character.',
        ];

        parent::__construct();
    }

    public function isValid($value): bool
    {
        $this->setValue($value);

        if (isset($this->options['check_length'])) {
            if (!empty($this->options['max']) && $this->options['max'] < strlen($value)) {
                $this->error(static::TOO_LONG);
                return false;
            }
            if (!empty($this->options['min']) && $this->options['min'] > strlen($value)) {
                $this->error(static::TOO_SHORT);
                return false;
            }
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
        if (isset($this->options['check_strong'])) {
            // Set pattern
            $pattern = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^a-zA-Z0-9\s]).+$/';

            // Get custom configs
            $configList = $this->configService->gtyConfigList();
            if (isset($configList['password']['configs']) && !empty($configList['password']['configs'])) {
                $pattern = $this->generatePasswordPattern($configList['password']['configs']);
            }

            $isPasswordStrong = $this->utilityService->isPasswordStrong($value, $pattern);
            if (!$isPasswordStrong) {
                $this->error(static::WEAK_PASSWORD);
                return false;
            }
        }

        return true;
    }

    private function generatePasswordPattern(array $config): string
    {
        $patterns = [];
        foreach ($config as $rule) {
            if (!empty($rule['value']) && $rule['value'] == 1) {
                switch ($rule['key']) {
                    case 'password_has_uppercase':
                        $patterns[] = '(?=.*[A-Z])';
                        break;
                    case 'password_has_lowercase':
                        $patterns[] = '(?=.*[a-z])';
                        break;
                    case 'password_has_number':
                        $patterns[] = '(?=.*\d)';
                        break;
                    case 'password_has_symbol':
                        $patterns[] = '(?=.*[^a-zA-Z0-9\s])'; // No whitespace, better security
                        break;
                }
            }
        }

        if (empty($patterns)) {
            return '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^a-zA-Z0-9\s]).+$/';
        }

        return '/^' . implode('', $patterns) . '.+$/';
    }
}