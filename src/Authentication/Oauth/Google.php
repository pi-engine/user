<?php

namespace Pi\User\Authentication\Oauth;

use Google\Auth\AccessToken;
use Hybridauth\Exception\UnexpectedApiResponseException;

class Google implements OauthInterface
{
    /* @var array */
    protected array $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @throws UnexpectedApiResponseException
     */
    public function verifyToken($params): array
    {
        // Set config
        $config = [];

        // Call service
        $accessToken = new AccessToken();
        $userProfile = $accessToken->verify($params['credential'], $config);

        return [
            'email'      => $userProfile['email'],
            'name'       => $userProfile['name'],
            'first_name' => $userProfile['given_name'],
            'last_name'  => $userProfile['family_name'],
        ];
    }
}