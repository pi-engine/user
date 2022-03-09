<?php

namespace User\Model;

class Role
{
    private        $id;
    private string $name;
    private string $title;
    private string $section;
    private        $status;

    public function __construct(
        $name,
        $title,
        $section,
        $status = null,
        $id = null
    ) {
        $this->name     = $name;
        $this->title = $title;
        $this->section    = $section;
        $this->status   = $status;
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
    public function getName(): string
    {
        return $this->name;
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
     * @return int
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }
}