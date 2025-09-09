<?php

declare(strict_types=1);

namespace Pi\User\Validator;

use AllowDynamicProperties;
use Laminas\Validator\AbstractValidator;
use Pi\User\Service\RoleService;

#[AllowDynamicProperties]
class RoleNameValidator extends AbstractValidator
{
    /** @var string */
    const string INVALID = 'nameInvalid';

    /** @var string */
    const string RESERVED = 'nameReserved';

    /** @var string */
    const string TAKEN = 'nameTaken';

    /** @var array */
    protected array $formatMessage = [];

    /**
     * Format pattern
     *
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
     *
     * @var array
     */
    protected $options
        = [
            'format'            => 'loose-space',
            'blacklist'         => [],
            'check_duplication' => false,
        ];

    /** @var RoleService */
    protected RoleService $roleService;

    /** @var array */
    protected array $messageTemplates;

    /**
     * {@inheritDoc}
     */
    public function __construct(
        RoleService $roleService,
                    $options = []
    ) {
        $this->roleService = $roleService;
        $this->options     = array_merge($this->options, $options);

        $this->messageTemplates = [
            self::INVALID  => 'Invalid name: %s',
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

        $isDuplicated = $this->roleService->isDuplicated('name', $value);
        if ($isDuplicated) {
            $this->error(static::TAKEN);
            return false;
        }

        if (!empty($this->options['blacklist'])) {
            $pattern = is_array($this->options['blacklist']) ? implode('|', $this->options['blacklist']) : $this->options['blacklist'];
            if (preg_match('/(' . $pattern . ')/', $value)) {
                $this->error(static::RESERVED);
                return false;
            }
        }

        return true;
    }
}
