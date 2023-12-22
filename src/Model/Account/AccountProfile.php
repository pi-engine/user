<?php

namespace User\Model\Account;

class AccountProfile
{
    private mixed $id;
    private ?string $name;
    private ?string $identity;
    private ?string $email;
    private ?string $mobile;
    private mixed $status;
    private mixed $time_created;
    private mixed $first_name;
    private mixed $last_name;
    private mixed $avatar;
    private mixed $birthdate;
    private mixed $gender;

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
     */
    public function __construct(mixed $id, ?string $name, ?string $identity, ?string $email, ?string $mobile, mixed $status, mixed $time_created, mixed $first_name, mixed $last_name, mixed $avatar, mixed $birthdate, mixed $gender)
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
    }

    /**
     * @return mixed
     */
    public function getId(): mixed
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getIdentity(): ?string
    {
        return $this->identity;
    }

    /**
     * @param string|null $identity
     */
    public function setIdentity(?string $identity): void
    {
        $this->identity = $identity;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * @param string|null $mobile
     */
    public function setMobile(?string $mobile): void
    {
        $this->mobile = $mobile;
    }

    /**
     * @return mixed
     */
    public function getStatus(): mixed
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus(mixed $status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getTimeCreated(): mixed
    {
        return $this->time_created;
    }

    /**
     * @param mixed $time_created
     */
    public function setTimeCreated(mixed $time_created): void
    {
        $this->time_created = $time_created;
    }

    /**
     * @return mixed
     */
    public function getFirstName(): mixed
    {
        return $this->first_name;
    }

    /**
     * @param mixed $first_name
     */
    public function setFirstName(mixed $first_name): void
    {
        $this->first_name = $first_name;
    }

    /**
     * @return mixed
     */
    public function getLastName(): mixed
    {
        return $this->last_name;
    }

    /**
     * @param mixed $last_name
     */
    public function setLastName(mixed $last_name): void
    {
        $this->last_name = $last_name;
    }

    /**
     * @return mixed
     */
    public function getAvatar(): mixed
    {
        return $this->avatar;
    }

    /**
     * @param mixed $avatar
     */
    public function setAvatar(mixed $avatar): void
    {
        $this->avatar = $avatar;
    }

    /**
     * @return mixed
     */
    public function getBirthdate(): mixed
    {
        return $this->birthdate;
    }

    /**
     * @param mixed $birthdate
     */
    public function setBirthdate(mixed $birthdate): void
    {
        $this->birthdate = $birthdate;
    }

    /**
     * @return mixed
     */
    public function getGender(): mixed
    {
        return $this->gender;
    }

    /**
     * @param mixed $gender
     */
    public function setGender(mixed $gender): void
    {
        $this->gender = $gender;
    }


}