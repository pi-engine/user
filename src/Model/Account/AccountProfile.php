<?php

declare(strict_types=1);

namespace Pi\User\Model\Account;

class AccountProfile
{
    private mixed   $id;
    private ?string $name;
    private ?string $identity;
    private ?string $email;
    private ?string $mobile;
    private mixed   $status;
    private mixed   $time_created;
    private mixed   $first_name;
    private mixed   $last_name;
    private mixed   $birthdate;
    private mixed   $gender;
    private mixed   $avatar;
    private mixed   $information;

    public function __construct(
        $name,
        $identity,
        $email,
        $mobile,
        $status,
        $time_created,
        $first_name,
        $last_name,
        $birthdate,
        $gender,
        $avatar,
        $information,
        $id = null
    ) {
        $this->name                = $name;
        $this->identity            = $identity;
        $this->email               = $email;
        $this->mobile              = $mobile;
        $this->status              = $status;
        $this->time_created        = $time_created;
        $this->first_name = $first_name;
        $this->last_name  = $last_name;
        $this->birthdate  = $birthdate;
        $this->gender     = $gender;
        $this->avatar     = $avatar;
        $this->information = $information;
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

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    /**
     * @return string|null
     */
    public function getBirthdate(): ?string
    {
        return $this->birthdate;
    }

    /**
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @return string|null
     */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /**
     * @return string|null
     */
    public function getInformation(): ?string
    {
        return $this->information;
    }
}