<?php

namespace Werkspot\BingAdsApiBundle\Guzzle;

use GuzzleHttp\ClientInterface;

class RequestNewAccessToken
{
    private $httpClient;

    const URL = 'https://login.live.com/oauth20_token.srf';
    const GRANTTYPE = 'refresh_token';

    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }


    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     * @param string $refreshToken
     *
     * @return array
     */
    public function get($clientId, $clientSecret, $redirectUri, $refreshToken)
    {
        $data = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => self::GRANTTYPE,
                'redirect_uri' => $redirectUri,
                'refresh_token' => $refreshToken,
            ]
        ];
        $response = $this->httpClient->request('POST', self::URL, $data);

        $json = json_decode($response->getBody(), true);
        $tokens = [
            'access' => $json['access_token'],
            'refresh' => $json['refresh_token'],
        ];

        return $tokens;
    }
}
