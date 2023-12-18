<?php

namespace User\Model\Account;

class MultiFactor
{
    private mixed   $id;
    private int $multi_factor_status;
    private ?string $multi_factor_secret;

    public function __construct(
        $multi_factor_status,
        $multi_factor_secret,
        $id = null
    ) {
        $this->multi_factor_status = $multi_factor_status;
        $this->multi_factor_secret = $multi_factor_secret;
        $this->id         = $id;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMultiFactorStatus(): int
    {
        return $this->multi_factor_status;
    }

    public function getMultiFactorSecret(): string|null
    {
        return $this->multi_factor_secret;
    }
}