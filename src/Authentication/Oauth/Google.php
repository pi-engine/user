<?php

namespace User\Authentication\Oauth;

use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Provider\Google as HybridauthGoogle;

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
        $configHybridauth = [
            'callback' => $this->config['google_callback'],
            'keys'     => [
                'id'     => $this->config['google_client_id'],
                'secret' => $this->config['google_client_secret'],
            ],
        ];

        // Call service
        $adapter = new HybridauthGoogle($configHybridauth);
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