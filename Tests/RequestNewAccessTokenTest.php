<?php

namespace Test\Werkspot\BingAdsApiBundle\Guzzle;

use Werkspot\BingAdsApiBundle\Guzzle\RequestNewAccessToken;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;


class RequestNewAccessTokenTest extends \PHPUnit_Framework_TestCase
{
    private $accessToken = "2ec09aeccaf634d982eec793037e37fe";
    private $refreshToken = "0c59f7e609b0cc467067e39d523116ce";


    public function testReturnedDataAccessTokenEquals()
    {
        $data = [
            "token_type" => "bearer",
            "expires_in" => 3600,
            "scope" => "bingads.manage",
            "access_token" => $this->accessToken,
            "refresh_token" => $this->refreshToken,
            "user_id" => "9fae2e51d79512efe5fe139a8ae9f885"
        ];

        $client = $this->createClient(null, $data);
        $guzzleClient = new RequestNewAccessToken($client);
        $response = $guzzleClient->get(null, null, null, null);
        $this->assertEquals($response['access'], $this->accessToken);
    }

    public function testReturnedDataRefreshTokenEquals()
    {
        $data = [
            "token_type" => "bearer",
            "expires_in" => 3600,
            "scope" => "bingads.manage",
            "access_token" => $this->accessToken,
            "refresh_token" => $this->refreshToken,
            "user_id" => "9fae2e51d79512efe5fe139a8ae9f885"
        ];

        $client = $this->createClient(null, $data);
        $guzzleClient = new RequestNewAccessToken($client);
        $response = $guzzleClient->get(null, null, null, null);
        $this->assertEquals($response['refresh'], $this->refreshToken);
    }

    public function testExceptionInvalidRefreshToken()
    {
        $this->expectException(\GuzzleHttp\Exception\ClientException::class);

        $data = [
            "error" => "invalid_grant",
            "error_description" => "The provided value for the input parameter 'refresh_token' is not valid."
        ];

        $client = $this->createClient(400, $data);
        $guzzleClient = new RequestNewAccessToken($client);
        $guzzleClient->get(null, null, null, null);

    }

    private function createClient($statusCode = 200, array $data)
    {

        $mock = new MockHandler([new Response($statusCode, [], json_encode($data))]);

        $handler = HandlerStack::create($mock);
        return new Client(['handler' => $handler]);
    }
}
