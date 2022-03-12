<?php

namespace User\Validator;

use Laminas\Validator\AbstractValidator;
use User\Service\AccountService;

class IdentityValidator extends AbstractValidator
{
    /** @var string */
    const INVALID = 'identityInvalid';

    /** @var string */
    const RESERVED = 'identityReserved';

    /** @var string */
    const TAKEN = 'identityTaken';

    /** @var string */
    protected string $formatHint;

    /** @var array */
    protected array $messageTemplates = [];

    /** @var array */
    protected array $formatMessage = [];

    /** @var array */
    protected array $formatPattern
        = [
            'strict'       => '/[^a-zA-Z0-9\_\-]/',
            'strict-space' => '/[^a-zA-Z0-9\_\-\s]/',
            'medium'       => '/[^a-zA-Z0-9\_\-\<\>\,\.\$\%\#\@\!\\\'\"]/',
            'medium-space' => '/[^a-zA-Z0-9\_\-\<\>\,\.\$\%\#\@\!\\\'\"\s]/',
            'loose'        => '/[\000-\040]/',
            'loose-space'  => '/[\000-\040][\s]/',
        ];

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
            self::INVALID  => 'Invalid identity: %formatHint%',
            self::RESERVED => 'Identity is reserved',
            self::TAKEN    => 'Identity is already taken',
        ];

        $this->formatMessage = [
            'strict'       => 'Only alphabetic and digits are allowed',
            'strict-space' => 'Only alphabetic, digits and spaces are allowed',
            'medium'       => 'Only ASCII characters are allowed',
            'medium-space' => 'Only ASCII characters and spaces are allowed',
            'loose'        => 'Only multi-byte characters are allowed',
            'loose-space'  => 'Only multi-byte characters and spaces are allowed',
        ];

        parent::__construct($options);
    }

    /**
     * identity validate
     *
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->setValue($value);
        $format = empty($this->options['format']) ? 'strict' : $this->options['format'];
        if (preg_match($this->formatPattern[$format], $value)) {
            $this->formatHint = $this->formatMessage[$format];
            $this->error(static::INVALID);
            return false;
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
            $isDuplicated = $this->accountService->isDuplicated('identity', $value, $userId);
            if ($isDuplicated) {
                $this->error(static::TAKEN);
                return false;
            }
        }

        return true;
    }
}
