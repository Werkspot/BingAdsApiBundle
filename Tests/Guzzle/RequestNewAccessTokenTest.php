<?php

namespace Tests\Werkspot\BingAdsApiBundle\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit_Framework_TestCase;
use Werkspot\BingAdsApiBundle\Guzzle\OauthTokenService;
use Werkspot\BingAdsApiBundle\Model\AccessToken;

class RequestNewAccessTokenTest extends PHPUnit_Framework_TestCase
{
    private $accessToken = '2ec09aeccaf634d982eec793037e37fe';
    private $refreshToken = '0c59f7e609b0cc467067e39d523116ce';

    public function testReturnedDataAccessTokenEquals()
    {
        $data = [
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'scope' => 'bingads.manage',
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'user_id' => '9fae2e51d79512efe5fe139a8ae9f885'
        ];

        $clientMock = Mockery::mock(Client::class);
        $clientMock
            ->shouldReceive('request')
            ->with('POST', OauthTokenService::URL, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'client_id' => 'client_id',
                    'client_secret' => 'client_secret',
                    'grant_type' => OauthTokenService::GRANTTYPE,
                    'redirect_uri' => 'redirect_uri',
                    'refresh_token' => $this->refreshToken
                ]
            ])
            ->once()
            ->andReturn(new Response(200, [], json_encode($data)));

        $guzzleClient = new OauthTokenService($clientMock);
        $response = $guzzleClient->refreshToken('client_id', 'client_secret', 'redirect_uri', new AccessToken(null, $this->refreshToken));

        $this->assertEquals($response->getAccessToken(), $this->accessToken);
        $this->assertEquals($response->getRefreshToken(), $this->refreshToken);
    }

    public function testClientException()
    {
        $this->expectException(ClientException::class);

        $data = [
            'error' => 'invalid_grant',
            'error_description' => "The provided value for the input parameter 'refresh_token' is not valid."
        ];
        $mock = new MockHandler([new Response(400, [], json_encode($data))]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $guzzleClient = new OauthTokenService($client);
        $guzzleClient->refreshToken(null, null, null, new AccessToken(null, null));
    }
}
