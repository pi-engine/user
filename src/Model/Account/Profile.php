<?php

declare(strict_types=1);

namespace Pi\User\Model\Account;

class Profile
{
    private mixed $id;
    private int   $user_id;
    private mixed $first_name;
    private mixed $last_name;
    private mixed $birthdate;
    private mixed $gender;
    private mixed $avatar;
    private mixed $information;

    public function __construct(
        $user_id,
        $first_name,
        $last_name,
        $birthdate,
        $gender,
        $avatar,
        $information,
        $id = null
    ) {
        $this->user_id     = $user_id;
        $this->first_name  = $first_name;
        $this->last_name   = $last_name;
        $this->birthdate   = $birthdate;
        $this->gender      = $gender;
        $this->avatar      = $avatar;
        $this->information = $information;
        $this->id          = $id;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @return string|null
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