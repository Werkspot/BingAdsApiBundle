<?php

namespace Werkspot\BingAdsApiBundle\Guzzle;

use GuzzleHttp\ClientInterface;
use Werkspot\BingAdsApiBundle\Model\AccessToken;

class OauthTokenService
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
     * @param AccessToken $accessToken
     *
     * @return AccessToken
     */
    public function refreshToken($clientId, $clientSecret, $redirectUri, AccessToken $accessToken)
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
                'refresh_token' => $accessToken->getRefreshToken(),
            ]
        ];
        $response = $this->httpClient->request('POST', self::URL, $data);

        $json = json_decode($response->getBody(), true);
        $tokens = new AccessToken($json['access_token'], $json['refresh_token']);

        return $tokens;
    }
}
