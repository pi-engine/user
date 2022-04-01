<?php

namespace User\Security;

use Psr\Http\Message\ServerRequestInterface;

interface SecurityInterface
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public function check(ServerRequestInterface $request): bool;

    /**
     * @return string
     */
    public function getErrorMessage(): string;

    /**
     * @return int
     */
    public function getStatusCode(): int;
}