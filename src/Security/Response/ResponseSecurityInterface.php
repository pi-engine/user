<?php

namespace User\Security\Response;

use Psr\Http\Message\ResponseInterface;

interface ResponseSecurityInterface
{
    /**
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function process(ResponseInterface $response): ResponseInterface;
}