<?php

namespace User\Model;

class Profile
{
    private        $id;

    public function __construct(
        $id = null
    ) {
        $this->id       = $id;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}