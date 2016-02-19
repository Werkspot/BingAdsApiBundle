<?php


namespace Werkspot\BingAdsApiBundle\Guzzle;

use Guzzle\Http\ClientInterface;

class RequestNewAccessToken
{
    private $httpClient;

    const URL = 'https://login.live.com/oauth20_token.srf';
    const GRANTTYPE = 'refresh_token';

    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }


    public function get($clientId, $clientSecret, $redirectUri, $refreshToken)
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $postData = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => self::GRANTTYPE,
            'redirect_uri' => $redirectUri,
            'refresh_token' => $refreshToken,
        ];
        $request = $this->httpClient->post(self::URL, $headers, $postData);
        $response = $request->send();

        $json = json_decode($response->getBody(), true);

        return $json['access_token'];

    }
}
