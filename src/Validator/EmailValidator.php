<?php

declare(strict_types=1);

namespace Pi\User\Validator;

use Laminas\Validator\EmailAddress;
use Laminas\Validator\Hostname;
use Pi\User\Service\AccountService;

class EmailValidator extends EmailAddress
{
    /** @var string */
    const RESERVED = 'userEmailReserved';

    /** @var string */
    const USED = 'userEmailUsed';

    const JustCompany = 'justCompany';

    /** @var array */
    protected $options
        = [
            'blacklist'               => [],
            'check_duplication'       => true,
            'useMxCheck'              => false,
            'useDeepMxCheck'          => false,
            'useDomainCheck'          => true,
            'allow'                   => Hostname::ALLOW_DNS,
            'strict'                  => true,
            'hostnameValidator'       => null,
            'allow_commercial_emails' => false,
        ];

    /** @var array */
    protected array $publicDomains
        = [
            'gmail.com',
            'yahoo.com',
            'outlook.com',
            'hotmail.com',
            'live.com',
            'aol.com',
            'icloud.com',
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
            self::RESERVED    => 'User email is reserved',
            self::USED        => 'User email is already used',
            self::JustCompany => 'Just commercial emails allowed',
        ];

        parent::__construct($this->options);
    }

    /**
     * User name validate
     *
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->setValue($value);

        $result = parent::isValid($value);
        if (!$result) {
            return false;
        }

        if ($this->options['allow_commercial_emails']) {
            $emailDomain = substr(strrchr($value, "@"), 1);
            if (in_array($emailDomain, $this->publicDomains)) {
                $this->error(self::INVALID_FORMAT, $value);
                return false;
            }
        }

        if (isset($this->options['blacklist']) && !empty($this->options['blacklist'])) {
            $pattern = is_array($this->options['blacklist']) ? implode('|', $this->options['blacklist']) : $this->options['blacklist'];
            if (preg_match('/(' . $pattern . ')/', $value)) {
                $this->error(static::RESERVED);
                return false;
            }
        }

        if ($this->options['check_duplication']) {
            $userId       = $this->options['user_id'] ?? 0;
            $isDuplicated = $this->accountService->isDuplicated('email', $value, $userId);
            if ($isDuplicated) {
                $this->error(static::USED);
                return false;
            }
        }

        return true;
    }
}
