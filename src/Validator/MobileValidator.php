<?php

declare(strict_types=1);

namespace Pi\User\Validator;

use Laminas\I18n\Validator\PhoneNumber;
use Laminas\Validator\AbstractValidator;
use Pi\User\Service\AccountService;
use function preg_match;

class MobileValidator extends AbstractValidator
{
    /** @var string */
    const string INVALID = 'mobileInvalid';

    /** @var string */
    const string RESERVED = 'mobileReserved';

    /** @var string */
    const string TAKEN = 'mobileTaken';

    /** @var array */
    protected array $messageTemplates = [];

    /** @var array */
    protected $options
        = [
            'format'            => 'strict',
            'blacklist'         => [],
            'check_duplication' => true,
        ];

    /** @var AccountService */
    protected AccountService $accountService;

    /**
     * {@inheritDoc}
     */
    public function __construct(
        AccountService $accountService,
                       $options = []
    ) {
        $this->accountService = $accountService;
        $this->options        = array_merge($this->options, $options);

        $this->messageTemplates = [
            self::INVALID  => 'Invalid mobile format',
            self::RESERVED => 'mobile is reserved',
            self::TAKEN    => 'mobile is already taken',
        ];

        parent::__construct($options);
    }

    /**
     * mobile validate
     *
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->setValue($value);

        // Set mobile validator
        if (isset($this->options['country']) && $this->options['country'] == 'IR') {
            $pattern = '/^\+989\d{9}$/';
            if (!preg_match($pattern, $value)) {
                $this->error(static::INVALID);
                return false;
            }
        } else {
            // ToDo: Update validator after update laminas project on this section
            $validator = new PhoneNumber();
            $validator->allowedTypes(['mobile']);
            if (isset($this->options['country'])) {
                $validator->setCountry($this->options['country']);
            }

            // Check
            if (!$validator->isValid($value)) {
                $this->error(static::INVALID);
                return false;
            }
        }

        if (!empty($this->options['blacklist'])) {
            $pattern = is_array($this->options['blacklist']) ? implode('|', $this->options['blacklist']) : $this->options['blacklist'];
            if (preg_match('/(' . $pattern . ')/', $value)) {
                $this->error(static::RESERVED);
                return false;
            }
        }

        if ($this->options['check_duplication']) {
            $userId       = $this->options['user_id'] ?? 0;
            $isDuplicated = $this->accountService->isDuplicated('mobile', $value, $userId);
            if ($isDuplicated) {
                $this->error(static::TAKEN);
                return false;
            }
        }

        return true;
    }
}
