<?php

namespace Pi\User\Authentication\Oauth;

interface OauthInterface
{
    public function verifyToken($params): array;
}