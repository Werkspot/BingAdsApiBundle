<?php

namespace Test\Werkspot\BingAdsApiBundle\Api;

use BingAds\Bulk\ReportTimePeriod;
use BingAds\Proxy\ClientProxy;
use Mockery;
use Mockery\MockInterface;
use PHPUnit_Framework_TestCase;
use Werkspot\BingAdsApiBundle\Api\Client;
use Werkspot\BingAdsApiBundle\Api\Exceptions;
use Werkspot\BingAdsApiBundle\Api\Helper;
use Werkspot\BingAdsApiBundle\Guzzle\OauthTokenService;
use Werkspot\BingAdsApiBundle\Model\AccessToken;
use Werkspot\BingAdsApiBundle\Model\ApiDetails;

class ClientTest extends PHPUnit_Framework_TestCase
{
    const ACCESS_TOKEN = '2ec09aeccaf634d982eec793037e37fe';
    const REFRESH_TOKEN = '0c59f7e609b0cc467067e39d523116ce';

    /**
     * @dataProvider getTestSoapExceptionData
     *
     * @param int $code
     * @param string $exceptionClassName
     */
    public function testSoapExceptions($code, $exceptionClassName)
    {
        $this->expectException($exceptionClassName);
        $this->runClientSoapException($code);
    }

    public function getTestSoapExceptionData()
    {
        return [
            0 => [
                'errorCode' => 0,
                'exceptionClassName' => Exceptions\SoapInternalErrorException::class
            ],
            105 => [
                'errorCode' => 105,
                'exceptionClassName' => Exceptions\SoapInvalidCredentialsException::class,
            ],
            106 => [
                'errorCode' => 106,
                'exceptionClassName' => Exceptions\SoapUserIsNotAuthorizedException::class,
            ],
            2004 => [
                'errorCode' => 2004,
                'exceptionClassName' => Exceptions\SoapNoCompleteDataAvailableException::class,
            ],
            2100 => [
                'errorCode' => 2100,
                'exceptionClassName' => Exceptions\SoapReportingServiceInvalidReportIdException::class,
            ],
            9999 => [
                'errorCode' => 9999,
                'exceptionClassName' => Exceptions\SoapUnknownErrorException::class,
            ],
        ];
    }

    /**
     * @return MockInterface
     */
    private function runClientSoapException($code)
    {
        $clientProxyMock = Mockery::mock(ClientProxy::class);
        $clientProxyMock
            ->shouldReceive('ConstructWithCredentials')
            ->andReturnSelf()
            ->once()
            ->shouldReceive('GetNamespace')
            ->andReturn('Namespace')
            ->shouldReceive('GetService')
            ->andThrow($this->generateSoapFault($code));

        $apiClient = $this->getApiClient(
            $this->getOauthTokenServiceMock(),
            new ApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken'),
            $clientProxyMock,
            new Helper\File(),
            new Helper\Csv(),
            $this->getTimeHelperMock()
        );
        $apiClient->get([], 'GeoLocationPerformanceReport', ReportTimePeriod::LastWeek);
    }

    /**
     * @return Mockery\MockInterface
     */
    private function getOauthTokenServiceMock()
    {
        $oauthTokenServiceMock = Mockery::mock(OauthTokenService::class);
        $oauthTokenServiceMock
            ->shouldReceive('refreshToken')
            ->with('clientId', 'clientSecret', 'redirectUri', AccessToken::class)
            ->once()
            ->andReturn(new AccessToken(self::ACCESS_TOKEN, self::REFRESH_TOKEN));

        return $oauthTokenServiceMock;
    }

    private function getTimeHelperMock()
    {
        $timeHelperMock = Mockery::mock(Helper\Time::class);
        $timeHelperMock->shouldReceive('sleep')->andReturnNull();

        return $timeHelperMock;
    }

    /**
     * @param OauthTokenService $oauthTokenService
     * @param ClientProxy $clientProxy
     * @param Helper\File $fileHelper
     * @param Helper\Csv $csvHelper
     * @param Helper\Time $timeHelper
     *
     * @return Client
     */
    private function getApiClient(OauthTokenService $oauthTokenService, ApiDetails $apiDetails, ClientProxy $clientProxy, Helper\File $fileHelper, Helper\Csv $csvHelper, Helper\Time $timeHelper)
    {
        $apiClient = new Client($oauthTokenService, $apiDetails, $clientProxy, $fileHelper, $csvHelper, $timeHelper);
        $apiClient->setConfig(['cache_dir' => '/tmp']);

        return $apiClient;
    }

    private function generateSoapFault($code)
    {
        $message = "an error message {$code}";
        $error = new \stdClass();
        $error->Code = $code;
        $error->Message = $message;
        $exception = new \SoapFault('Server', '');
        $exception->detail = new \stdClass();
        $exception->detail->AdApiFaultDetail = new \stdClass();
        $exception->detail->AdApiFaultDetail->Errors = new \stdClass();
        $exception->detail->AdApiFaultDetail->Errors->AdApiError = [$error];

        return $exception;
    }
}
