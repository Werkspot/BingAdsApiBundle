<?php
namespace Test\Werkspot\BingAdsApiBundle\Model;

use PHPUnit_Framework_TestCase;
use Werkspot\BingAdsApiBundle\Model\ApiDetails;

class ApiDetailsTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $refreshToken = 'refreshToken';
        $clientId = 'clientId';
        $secret = 'secret';
        $redirectUri = 'redirectUri';
        $devToken = 'devToken';

        $apiDetails = new ApiDetails($refreshToken, $clientId, $secret, $redirectUri, $devToken);

        $this->assertEquals($refreshToken, $apiDetails->getRefreshToken());
        $this->assertEquals($clientId, $apiDetails->getClientId());
        $this->assertEquals($secret, $apiDetails->getSecret());
        $this->assertEquals($redirectUri, $apiDetails->getRedirectUri());
        $this->assertEquals($devToken, $apiDetails->getDevToken());
    }

    public function testSetRefreshToken()
    {
        $refreshToken = 'refreshToken';
        $apiDetails = new ApiDetails(null, null, null, null, null);
        $this->assertNull($apiDetails->getRefreshToken());
        $apiDetails->setRefreshToken($refreshToken);
        $this->assertEquals($refreshToken, $apiDetails->getRefreshToken());
        $this->assertNull($apiDetails->getClientId());
        $this->assertNull($apiDetails->getSecret());
        $this->assertNull($apiDetails->getRedirectUri());
        $this->assertNull($apiDetails->getDevToken());
    }
}
