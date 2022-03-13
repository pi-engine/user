<?php

namespace User\Model;

class PermissionResource
{
    private        $id;
    private string $title;
    private string $section;
    private string $module;
    private string $name;
    private string $type;

    public function __construct(
        $title,
        $section,
        $module,
        $name,
        $type,
        $id = null
    ) {
        $this->title   = $title;
        $this->section = $section;
        $this->module  = $module;
        $this->name    = $name;
        $this->type    = $type;
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
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}