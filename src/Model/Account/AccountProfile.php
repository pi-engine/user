<?php

namespace User\Model\Account;

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
    private mixed   $avatar;
    private mixed   $birthdate;
    private mixed   $gender;
    private mixed   $information;

    /**
     * @param mixed $id
     * @param string|null $name
     * @param string|null $identity
     * @param string|null $email
     * @param string|null $mobile
     * @param mixed $status
     * @param mixed $time_created
     * @param mixed $first_name
     * @param mixed $last_name
     * @param mixed $avatar
     * @param mixed $birthdate
     * @param mixed $gender
     * @param mixed $information
     */
    public function __construct(mixed $id, ?string $name, ?string $identity, ?string $email, ?string $mobile, mixed $status, mixed $time_created, mixed $first_name, mixed $last_name, mixed $avatar, mixed $birthdate, mixed $gender, mixed $information)
    {
        $this->id = $id;
        $this->name = $name;
        $this->identity = $identity;
        $this->email = $email;
        $this->mobile = $mobile;
        $this->status = $status;
        $this->time_created = $time_created;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->avatar = $avatar;
        $this->birthdate = $birthdate;
        $this->gender = $gender;
        $this->information = $information;
    }

    public function getId(): mixed
    {
        return $this->id;
    }

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getIdentity(): ?string
    {
        return $this->identity;
    }

    public function setIdentity(?string $identity): void
    {
        $this->identity = $identity;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(?string $mobile): void
    {
        $this->mobile = $mobile;
    }

    public function getStatus(): mixed
    {
        return $this->status;
    }

    public function setStatus(mixed $status): void
    {
        $this->status = $status;
    }

    public function getTimeCreated(): mixed
    {
        return $this->time_created;
    }

    public function setTimeCreated(mixed $time_created): void
    {
        $this->time_created = $time_created;
    }

    public function getFirstName(): mixed
    {
        return $this->first_name;
    }

    public function setFirstName(mixed $first_name): void
    {
        $this->first_name = $first_name;
    }

    public function getLastName(): mixed
    {
        return $this->last_name;
    }

    public function setLastName(mixed $last_name): void
    {
        $this->last_name = $last_name;
    }

    public function getAvatar(): mixed
    {
        return $this->avatar;
    }

    public function setAvatar(mixed $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function getBirthdate(): mixed
    {
        return $this->birthdate;
    }

    public function setBirthdate(mixed $birthdate): void
    {
        $this->birthdate = $birthdate;
    }

    public function getGender(): mixed
    {
        return $this->gender;
    }

    public function setGender(mixed $gender): void
    {
        $this->gender = $gender;
    }

    public function getInformation(): mixed
    {
        return $this->information;
    }

    public function setInformation(mixed $information): void
    {
        $this->information = $information;
    }



}




