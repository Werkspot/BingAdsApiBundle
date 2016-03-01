<?php

namespace Test\Werkspot\BingAdsApiBundle\Api;

use BingAds\Proxy\ClientProxy;
use Mockery;
use Werkspot\BingAdsApiBundle\Api\Client;
use Werkspot\BingAdsApiBundle\Guzzle\RequestNewAccessToken;
use Werkspot\BingAdsApiBundle\Api\Helper;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    private $accessToken = "2ec09aeccaf634d982eec793037e37fe";
    private $refreshToken = "0c59f7e609b0cc467067e39d523116ce";

    public function testGeoLocationPerformanceReport()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');

        $clientProxyMock = $this->getClientProxyMock();
        $requestNewAccessTokenMock = $this->getRequestNewAccessTokenMock();
        $zipHelperMock = $this->getZipHelperMock();

        $apiClient = $this->getApiClient($requestNewAccessTokenMock, $clientProxyMock, $zipHelperMock);
        $apiClient->get(['TimePeriod', 'AccountName', 'AdGroupId']);
    }

    /**
     * @return Mockery\MockInterface
     */
    private function getClientProxyMock()
    {
        $clientProxyMock = Mockery::mock(ClientProxy::class);
        $clientProxyMock
            ->shouldReceive('ConstructWithCredentials')
            ->once();

        return $clientProxyMock;
    }

    /**
     * @return Mockery\MockInterface
     */
    private function getRequestNewAccessTokenMock()
    {
        $requestNewAccessTokenMock = Mockery::mock(RequestNewAccessToken::class);
        $requestNewAccessTokenMock
            ->shouldReceive('get')
            ->with('clientId', 'clientSecret', 'redirectUri', 'refreshToken')
            ->once()
            ->andReturn(['access' => $this->accessToken,'refresh' => $this->refreshToken]);
        return $requestNewAccessTokenMock;
    }

    /**
     * @return Mockery\MockInterface
     */
    private function getZipHelperMock()
    {
        $zipHelperMock = Mockery::mock(Helper\Zip::class);
        $zipHelperMock
            ->shouldReceive('download')
            ->andReturn('/tmp/report.zip')
            ->once();

        $zipHelperMock
            ->shouldReceive('unZip')
            ->with('/tmp/report.zip')
            ->andReturn(['/tmp/report.csv'])
            ->once();

        return $zipHelperMock;
    }

    /**
     * @param RequestNewAccessToken $requestNewAccessToken
     * @param ClientProxy $clientProxy
     * @param Helper\Zip $zipHelper
     *
     * @return Client
     */
    private function getApiClient(RequestNewAccessToken $requestNewAccessToken, ClientProxy $clientProxy, Helper\Zip $zipHelper)
    {
        $apiClient = new Client($requestNewAccessToken, $clientProxy, $zipHelper);
        $apiClient->setConfig(['cache_dir' => '/tmp']);
        $apiClient->setApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken');

        return $apiClient;
    }
}
