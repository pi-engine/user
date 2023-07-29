<?php

namespace User\Security;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

class Xss implements SecurityInterface
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public function check(ServerRequestInterface $request): bool
    {
        // Get request body
        $params = $request->getParsedBody();

        foreach ($params as $param) {
            if ($param != htmlspecialchars($param, ENT_QUOTES, 'UTF-8')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'XSS attack detected !';
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return StatusCodeInterface::STATUS_BAD_REQUEST;
    }
}