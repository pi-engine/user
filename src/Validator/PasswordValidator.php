<?php

namespace User\Validator;

use Laminas\Validator\AbstractValidator;

class PasswordValidator extends AbstractValidator
{
    const TOO_SHORT = 'stringLengthTooShort';
    const TOO_LONG  = 'stringLengthTooLong';

    protected array $messageVariables
        = [
            'max' => 'max',
            'min' => 'min',
        ];

    protected $max;
    protected $min;

    private array $messageTemplates;

    private $options;

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
        $this->setConfigOption();

        if (!empty($this->options['max'])
            && $this->options['max'] < strlen($value)
        ) {
            $this->max = $this->options['max'];
            $this->error(static::TOO_LONG);
            return false;
        }
        if (!empty($this->options['min'])
            && $this->options['min'] > strlen($value)
        ) {
            $this->min = $this->options['min'];
            $this->error(static::TOO_SHORT);
            return false;
        }

        return true;
    }

    /**
     * Set username validator according to config
     *
     * @return $this
     */
    public function setConfigOption()
    {
        $this->options = [
            'min' => 8,
            'max' => 32,
        ];

        return $this;
    }
}
