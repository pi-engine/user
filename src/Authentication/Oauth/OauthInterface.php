<?php

declare(strict_types=1);

namespace Pi\User\Authentication\Oauth;

interface OauthInterface
{
    public function verifyToken($params): array;
}