<?php

namespace User\Model\Account;

class Profile
{
    private mixed $id;
    private int   $user_id;
    private mixed $first_name;
    private mixed $last_name;
    private mixed $birthdate;
    private mixed $gender;
    private mixed $avatar;
    //private mixed $id_number;
    //private mixed $ip_register;
    //private mixed $register_source;
    //private mixed $homepage;
    //private mixed $phone;
    //private mixed $address_1;
    //private mixed $address_2;
    //private mixed $item_id;
    //private mixed $country;
    //private mixed $state;
    //private mixed $city;
    //private mixed $zip_code;
    //private mixed $bank_name;
    //private mixed $bank_card;
    //private mixed $bank_account;
    private mixed $information;

    public function __construct(
        $user_id,
        $first_name,
        $last_name,
        $birthdate,
        $gender,
        $avatar,
        //$id_number,
        //$ip_register,
        //$register_source,
        //$homepage,
        //$phone,
        //$address_1,
        //$address_2,
        //$item_id,
        //$country,
        //$state,
        //$city,
        //$zip_code,
        //$bank_name,
        //$bank_card,
        //$bank_account,
        $information,
        $id = null
    ) {
        $this->user_id    = $user_id;
        $this->first_name = $first_name;
        $this->last_name  = $last_name;
        $this->birthdate  = $birthdate;
        $this->gender     = $gender;
        $this->avatar     = $avatar;
        //$this->id_number       = $id_number;
        //$this->ip_register     = $ip_register;
        //$this->register_source = $register_source;
        //$this->homepage        = $homepage;
        //$this->phone           = $phone;
        //$this->address_1       = $address_1;
        //$this->address_2       = $address_2;
        //$this->item_id         = $item_id;
        //$this->country         = $country;
        //$this->state           = $state;
        //$this->city            = $city;
        //$this->zip_code        = $zip_code;
        //$this->bank_name       = $bank_name;
        //$this->bank_card       = $bank_card;
        //$this->bank_account    = $bank_account;
        $this->information = $information;
        $this->id          = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    /**
     * @return string
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

    /* public function getIdNumber(): ?string
    {
        return $this->id_number;
    }

    public function getIpRegister(): ?string
    {
        return $this->ip_register;
    }

    public function getRegisterSource(): ?string
    {
        return $this->register_source;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAddress1(): ?string
    {
        return $this->address_1;
    }

    public function getAddress2(): ?string
    {
        return $this->address_2;
    }

    public function getItemId(): ?string
    {
        return $this->item_id;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getZipCode(): ?string
    {
        return $this->zip_code;
    }

    public function getBankName(): ?string
    {
        return $this->bank_name;
    }

    public function getBankCard(): ?string
    {
        return $this->bank_card;
    }

    public function getBankAccount(): ?string
    {
        return $this->bank_account;
    } */

    public function getInformation(): ?string
    {
        return $this->information;
    }
}