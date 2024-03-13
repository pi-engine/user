<?php

namespace User\Model\Account;

class Account
{
    private mixed   $id;
    private ?string $name;
    private ?string $identity;
    private ?string $email;
    private ?string $mobile;
    private mixed   $status;
    private mixed   $time_created;
    private int     $multi_factor_status;

    public function __construct(
        $name,
        $identity,
        $email,
        $mobile,
        $status = null,
        $time_created = null,
        $multi_factor_status = 0,
        $id = null
    ) {
        $this->name                = $name;
        $this->identity            = $identity;
        $this->email               = $email;
        $this->mobile              = $mobile;
        $this->status              = $status;
        $this->time_created        = $time_created;
        $this->multi_factor_status = $multi_factor_status;
        $this->id                  = $id;
    }

    /**
     * @return int|null
     */
    public function getId(): int|null
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): string|null
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getIdentity(): string|null
    {
        return $this->identity;
    }

    /**
     * @return string|null
     */
    public function getEmail(): string|null
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getMobile(): string|null
    {
        return $this->mobile;
    }

    /**
     * @return int|null
     */
    public function getStatus(): int|null
    {
        return $this->status;
    }

    /**
     * @return int|null
     */
    public function getTimeCreated(): int|null
    {
        return $this->time_created;
    }

    public function getMultiFactorStatus(): int
    {
        return $this->multi_factor_status;
    }
}