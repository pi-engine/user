<?php

namespace User\Model\Account;

class Credential
{
    private        $id;
    private string $credential;

    public function __construct(
        $credential,
        $id = null
    ) {
        $this->credential = $credential;
        $this->id         = $id;
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
    public function getCredential(): string
    {
        return $this->credential;
    }
}