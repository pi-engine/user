<?php

namespace User\Validator;

use Laminas\Validator\AbstractValidator;

class PasswordValidator extends AbstractValidator
{
    const TOO_SHORT = 'stringLengthTooShort';
    const TOO_LONG = 'stringLengthTooLong';

    /** @var int */
    protected int $max;

    /** @var int */
    protected int $min;

    /** @var array */
    protected array $messageTemplates = [];

    /** @var array */
    protected $options = [
        'min' => 8,
        'max' => 32,
    ];

    public function __construct()
    {
        $this->messageTemplates = [
            self::TOO_SHORT => 'Password is less than %min% characters long',
            self::TOO_LONG  => 'Password is more than %max% characters long',
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

        return true;
    }
}
