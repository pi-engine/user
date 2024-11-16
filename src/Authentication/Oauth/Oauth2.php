<?php

namespace Pi\User\Authentication\Oauth;


class Oauth2 implements OauthInterface
{
    /* @var array */
    protected array $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     */
    public function verifyToken($params): array
    {
        $tokenQueryFormat = "client_id=%s&client_secret=%s&grant_type=%s&code=%s&redirect_uri=%s";
        // Use sprintf to format the string with variables from the parsed query
        $formattedString = sprintf(
            $tokenQueryFormat,
            $this->config['client_id'],
            $this->config['client_secret'],
            $this->config['grant_type'],
            $params['code'],
            $this->config['redirect_uri']
        );
        // Call service
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->config['token_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        //curl_setopt($ch, CURLOPT_COOKIE, '.AspNetCore.Antiforgery.5i-g8aAvrt8=CfDJ8HmUtFbpSIpLuxobDPXFk9EX73Y3cntxabkGAn1g0OHt2oNKUtK4bwU33aAL1894IzTQXmnZD6BfOMCQpfVIdjPAfaB_xjdHruhZ7-saQV-9vvojBw9olYJg8IoA7y5AX3DUrrc1zNg6g_utQXjjNNw; .AspNetCore.Culture=c%3Dfa%7Cuic%3Dfa');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formattedString);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, 'client_id=ecc2a7c9-a9ab-43a9-b57f-d4f28e00d350&client_secret=72e23c58-5fd4-8c9a-ece5-9ea27e335622&grant_type=authorization_code&code=B198E0D9C3A1590CB1A52BAAA350F923EBA6FE713AF12DBBF5C5D5309D2AFFFA&redirect_uri=https%3A%2F%2Fcompliance.shahr-bank.ir%2Fredirect-test');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);


        $response = json_decode($response, true);
        if (!isset($response['access_token'])) {
            return [
                'result' => false,
                'data'   => null,
                'error'  => [
                    'message' => 'Invalid authentication data. please try again!!',
                ],
            ];
        }


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->config['user_info_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            sprintf('Authorization: Bearer %s', $response['access_token']),
        ]);
        //curl_setopt($ch, CURLOPT_COOKIE, '.AspNetCore.Antiforgery.5i-g8aAvrt8=CfDJ8HmUtFbpSIpLuxobDPXFk9EX73Y3cntxabkGAn1g0OHt2oNKUtK4bwU33aAL1894IzTQXmnZD6BfOMCQpfVIdjPAfaB_xjdHruhZ7-saQV-9vvojBw9olYJg8IoA7y5AX3DUrrc1zNg6g_utQXjjNNw; .AspNetCore.Culture=c%3Dfa%7Cuic%3Dfa');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $userInfo = curl_exec($ch);
        curl_close($ch);

        $userInfo = json_decode($userInfo, true);
        if (!isset($userInfo['NId'])) {
            return [
                'result' => false,
                'data'   => null,
                'error'  => [
                    'message' => 'Invalid authentication data. please try again!!!',
                ],
            ];
        }

        return [
            'result' => true,
            'data'   => [
                'identity'   => $userInfo['NId'],
                'name'       => $userInfo['name'],
                'first_name' => $userInfo['given_name'],
                'last_name'  => $userInfo['family_name'],
            ],
            'error'  => [],
        ];
    }
}