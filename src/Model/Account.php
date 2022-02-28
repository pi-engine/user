<?php

namespace User\Model;

class Account
{
    private        $id;
    private string $name;
    private string $identity;
    private string $email;
    private        $status;

    public function __construct(
        $name,
        $identity,
        $email,
        $status = null,
        $id = null
    ) {
        $this->name     = $name;
        $this->identity = $identity;
        $this->email    = $email;
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
     * @return int
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }
}