<?php

namespace User\Model;

class AccountRole
{
    private        $id;
    private int    $user_id;
    private string $role;
    private string $section;

    public function __construct(
        $userId,
        $role,
        $section,
        $id = null
    ) {
        $this->user_id = $userId;
        $this->role    = $role;
        $this->section = $section;
        $this->id      = $id;
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
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @return string
     */
    public function getSection(): string
    {
        return $this->section;
    }
}