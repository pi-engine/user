<?php

namespace User\Authentication\Oauth;

use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Provider\MicrosoftGraph as HybridauthMicrosoftGraph;


class Oauth2 implements OauthInterface
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
        $configHybridauth = [

        ];

        // Call service


        return [
            'email'      => 'email',
            'name'       => 'displayName',
            'first_name' => 'firstName',
            'last_name'  => 'lastName',
        ];
    }
}