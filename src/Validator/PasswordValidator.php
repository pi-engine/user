<?php

namespace User\Validator;

use Laminas\Validator\AbstractValidator;
use function sprintf;

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
    protected $options
        = [
            'min' => 8,
            'max' => 32,
        ];

    public function __construct()
    {
        $this->messageTemplates = [
            self::TOO_SHORT => sprintf('Password is less than %s characters long', (int)$this->options['min']),
            self::TOO_LONG  => sprintf('Password is more than %s characters long', (int)$this->options['max']),
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

        return true;
    }
}
