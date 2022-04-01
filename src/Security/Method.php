<?php

namespace User\Security;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

class Method implements SecurityInterface
{
    /** @var array|string[] */
    protected array $allowMethod = ['POST'];

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public function check(ServerRequestInterface $request): bool
    {
        // Get request method
        $method = $request->getMethod();
        if (!in_array($method, $this->allowMethod)) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Request method not allowed !';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_UNAUTHORIZED;
    }
}