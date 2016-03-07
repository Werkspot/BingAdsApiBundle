<?php


namespace Tests\Werkspot\BingAdsApiBundle\Model;

use PHPUnit_Framework_TestCase;
use Werkspot\BingAdsApiBundle\Model\AccessToken;

class AccessTokenTest extends PHPUnit_Framework_TestCase
{
    const ACCESS_TOKEN = '2ec09aeccaf634d982eec793037e37fe';
    const REFRESH_TOKEN = '0c59f7e609b0cc467067e39d523116ce';

    public function testAccessTokenConstructor()
    {
        $accessToken = new AccessToken(self::ACCESS_TOKEN, self::REFRESH_TOKEN);
        $this->assertEquals(self::ACCESS_TOKEN, $accessToken->getAccessToken());
        $this->assertEquals(self::REFRESH_TOKEN, $accessToken->getRefreshToken());

    }
}
