<?php

namespace User\Validator;

use Laminas\Validator\AbstractValidator;
use User\Service\AccountService;

class NameValidator extends AbstractValidator
{
    /** @var string */
    const INVALID = 'nameInvalid';

    /** @var string */
    const RESERVED = 'nameReserved';

    /** @var string */
    const TAKEN = 'nameTaken';

    /**
     * Message variables
     * @var array
     */
    protected array $messageVariables
        = [
            'formatHint' => 'formatHint',
        ];

    /**
     * Format hint
     * @var string
     */
    protected string $formatHint;

    /** @var array */
    protected $messageTemplates = [];

    /** @var array */
    protected array $formatMessage = [];

    /**
     * Format pattern
     * @var array
     */
    protected array $formatPattern
        = [
            'strict'       => '/[^a-zA-Z0-9\_\-]/',
            'strict-space' => '/[^a-zA-Z0-9\_\-\s]/',
            'medium'       => '/[^a-zA-Z0-9\_\-\<\>\,\.\$\%\#\@\!\\\'\"]/',
            'medium-space' => '/[^a-zA-Z0-9\_\-\<\>\,\.\$\%\#\@\!\\\'\"\s]/',
            'loose'        => '/[\000-\040]/',
            'loose-space'  => '/[\000-\040][\s]/',
        ];

    /**
     * Options
     * @var array
     */
    protected $options
        = [
            'format'            => 'medium-space',
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

        $this->messageTemplates = $this->messageTemplates + [
                self::INVALID  => 'Invalid name: %formatHint%',
                self::RESERVED => 'Name is reserved',
                self::TAKEN    => 'Name is already taken',
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
     * User name validate
     *
     * @param  mixed     $value
     * @param array|null $context
     *
     * @return bool
     */
    public function isValid($value, array $context = null): bool
    {
        $this->setValue($value);
        $format = empty($this->options['format'])
            ? 'strict' : $this->options['format'];
        if (preg_match($this->formatPattern[$format], $value)) {
            $this->formatHint = $this->formatMessage[$format];
            $this->error(static::INVALID);
            return false;
        }

        if (!empty($this->options['blacklist'])) {
            $pattern = is_array($this->options['blacklist'])
                ? implode('|', $this->options['blacklist'])
                : $this->options['blacklist'];
            if (preg_match('/(' . $pattern . ')/', $value)) {
                $this->error(static::RESERVED);
                return false;
            }
        }

        if ($this->options['check_duplication']) {
            $isDuplicated = $this->accountService->isDuplicated('name', $value);
            if ($isDuplicated) {
                $this->error(static::TAKEN);
                return false;
            }
        }

        return true;
    }
}
