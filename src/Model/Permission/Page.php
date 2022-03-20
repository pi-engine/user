<?php

namespace User\Model\Permission;

class Page
{
    private        $id;
    private string $title;
    private string $section;
    private string $module;
    private string $package;
    private string $handler;
    private string $resource;
    private string $cache_type;
    private int    $cache_ttl;
    private string $cache_level;

    public function __construct(
        $title,
        $section,
        $module,
        $package,
        $handler,
        $resource,
        $cache_type,
        $cache_ttl,
        $cache_level,
        $id = null
    ) {
        $this->title       = $title;
        $this->section     = $section;
        $this->module      = $module;
        $this->package     = $package;
        $this->handler     = $handler;
        $this->resource    = $resource;
        $this->cache_type  = $cache_type;
        $this->cache_ttl   = $cache_ttl;
        $this->cache_level = $cache_level;
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
    public function getPackage(): string
    {
        return $this->package;
    }

    /**
     * @return string
     */
    public function getHandler(): string
    {
        return $this->handler;
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
    public function getCacheType(): string
    {
        return $this->cache_type;
    }

    /**
     * @return int
     */
    public function getCacheTtl(): int
    {
        return $this->cache_ttl;
    }

    /**
     * @return string
     */
    public function getCacheLevel(): string
    {
        return $this->cache_level;
    }
}