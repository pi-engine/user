<?php

namespace User\Model\Account;

class Account
{
    private mixed  $id;
    private string $name;
    private ?string $identity;
    private ?string $email;
    private ?string $mobile;
    private mixed   $status;

    public function __construct(
        $name,
        $identity,
        $email,
        $mobile,
        $status = null,
        $id = null
    ) {
        $this->name     = $name;
        $this->identity = $identity;
        $this->email    = $email;
        $this->mobile   = $mobile;
        $this->status   = $status;
        $this->id       = $id;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getIdentity(): string
    {
        return $this->identity;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * @return int
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }
}