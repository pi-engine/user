<?php

namespace User\Authentication\Oauth;

use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Provider\MicrosoftGraph as HybridauthMicrosoftGraph;


class Microsoft implements OauthInterface
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
            'callback' => $this->config['microsoft_callback'],
            'keys'     => [
                'id'     => $this->config['microsoft_client_id'],
                'secret' => $this->config['microsoft_client_secret'],
            ],
            'tenant'   => $this->config['microsoft_tenant_id'],
        ];

        // Call service
        $adapter = new HybridauthMicrosoftGraph($configHybridauth);
        $adapter->setAccessToken($params['token']);
        $userProfile = (array)$adapter->getUserProfile();

        return [
            'email'      => $userProfile['email'],
            'name'       => $userProfile['displayName'],
            'first_name' => $userProfile['firstName'],
            'last_name'  => $userProfile['lastName'],
        ];
    }
}