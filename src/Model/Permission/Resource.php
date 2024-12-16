<?php

declare(strict_types=1);

namespace Pi\User\Model\Permission;

class Resource
{
    private        $id;
    private string $title;
    private string $section;
    private string $module;
    private string $key;
    private string $type;

    public function __construct(
        $title,
        $section,
        $module,
        $key,
        $type,
        $id = null
    ) {
        $this->title   = $title;
        $this->section = $section;
        $this->module  = $module;
        $this->key     = $key;
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
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}