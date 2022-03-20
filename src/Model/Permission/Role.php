<?php

namespace User\Model\Permission;

class Role
{
    private        $id;
    private string $resource;
    private string $section;
    private string $module;
    private string $role;

    public function __construct(
        $resource,
        $section,
        $module,
        $role,
        $id = null
    ) {
        $this->resource = $resource;
        $this->section  = $section;
        $this->module   = $module;
        $this->role     = $role;
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
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * @return string
     */
    public function getSection(): string
    {
        return $this->section;
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }
}