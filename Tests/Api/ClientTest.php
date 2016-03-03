<?php

namespace Test\Werkspot\BingAdsApiBundle\Api;

use BingAds\Bulk\ReportTimePeriod;
use BingAds\Proxy\ClientProxy;
use Mockery;
use Symfony\Component\Filesystem\Filesystem;
use Werkspot\BingAdsApiBundle\Api\Client;
use Werkspot\BingAdsApiBundle\Api\Exceptions;
use Werkspot\BingAdsApiBundle\Api\Helper;
use Werkspot\BingAdsApiBundle\Guzzle\OauthTokenService;
use Werkspot\BingAdsApiBundle\Model\AccessToken;
use PHPUnit_Framework_TestCase;

class ClientTest extends PHPUnit_Framework_TestCase
{
    private $accessToken = '2ec09aeccaf634d982eec793037e37fe';
    private $refreshToken = '0c59f7e609b0cc467067e39d523116ce';

    public function testGeoLocationPerformanceReportReturnsArrayWithCsv()
    {
        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            $this->getClientProxyMock(),
            $this->getFileHelperMock(),
            $this->getCsvHelperMock(),
            $this->getTimeHelperMock()
        );
        $result = $apiClient->get(['TimePeriod', 'AccountName', 'AdGroupId'], 'GeoLocationPerformanceReport', ReportTimePeriod::LastWeek);
        $this->assertEquals([ASSETS_DIR . 'report.csv'], $result);
    }

    public function testGeoLocationPerformanceReportMoveFile()
    {
        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            $this->getClientProxyMock(),
            $this->getFileHelperMock(),
            $this->getCsvHelperMock(),
            $this->getTimeHelperMock()
        );
        $result = $apiClient->get(['TimePeriod', 'AccountName', 'AdGroupId'], 'GeoLocationPerformanceReport', ReportTimePeriod::LastWeek, ASSETS_DIR . 'test.csv');
        $this->assertEquals(ASSETS_DIR . 'test.csv', $result);

        //--Move File back
        $fileSystem = new Filesystem();
        $fileSystem->rename(ASSETS_DIR . 'test.csv', ASSETS_DIR . 'report.csv');
    }

    public function testGetRefreshToken()
    {
        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            $this->getClientProxyMock(),
            $this->getFileHelperMock(),
            $this->getCsvHelperMock(),
            $this->getTimeHelperMock()
        );

        $this->assertEquals('refreshToken', $apiClient->getRefreshToken());

        $apiClient->get(['TimePeriod', 'AccountName', 'AdGroupId'], 'GeoLocationPerformanceReport', ReportTimePeriod::LastWeek);
        $this->assertEquals($this->refreshToken, $apiClient->getRefreshToken());
    }

    public function testGeoLocationPerformanceReportTimeoutException()
    {
        $this->expectException(Exceptions\RequestTimeoutException::class);
        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            $this->getClientProxyMock('Pending'),
            new Helper\File(),
            new Helper\Csv(),
            $this->getTimeHelperMock()
        );
        $apiClient->get(['TimePeriod', 'AccountName', 'AdGroupId'], 'GeoLocationPerformanceReport');
    }

    public function testGeoLocationPerformanceReportRequestErrorException()
    {
        $this->expectException(Exceptions\ReportRequestErrorException::class);
        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            $this->getClientProxyMock('Error'),
            new Helper\File(),
            new Helper\Csv(),
            $this->getTimeHelperMock()
        );
        $apiClient->get(['TimePeriod', 'AccountName', 'AdGroupId'], 'GeoLocationPerformanceReport');
    }

    public function testPollGenerateReportSoapException()
    {
        $clientProxyMock = Mockery::mock(ClientProxy::class);
        $clientProxyMock->ReportRequestId = 'reportID';
        $clientProxyMock
            ->shouldReceive('ConstructWithCredentials')
            ->andReturnSelf()
            ->once()
            ->shouldReceive('GetNamespace')
            ->once()
            ->andReturn('Namespace')
            ->shouldReceive('GetService')
            ->twice()
            ->andReturnSelf()
            ->shouldReceive('SubmitGenerateReport')
            ->once()
            ->andReturnSelf()
            ->shouldReceive('PollGenerateReport')
            ->andThrow($this->generateSoapFault(0));
        $this->expectException(Exceptions\SoapInternalErrorException::class);
        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            $clientProxyMock,
            new Helper\File(),
            new Helper\Csv(),
            $this->getTimeHelperMock()
        );
        $apiClient->get(['TimePeriod', 'AccountName', 'AdGroupId'], 'GeoLocationPerformanceReport');
    }

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
     * @return Mockery\MockInterface
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
            $this->getRequestNewAccessTokenMock(),
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
    private function getClientProxyMock($reportStatus = 'Success')
    {
        $clientProxyMock = Mockery::mock(ClientProxy::class);
        $clientProxyMock
            ->shouldReceive('ConstructWithCredentials')
            ->andReturnSelf()
            ->once()
            ->shouldReceive('GetNamespace')
            ->between(1, 48)
            ->andReturn('Namespace')
            ->shouldReceive('GetService')
            ->between(2, 49)
            ->andReturnSelf()
            ->shouldReceive('SubmitGenerateReport')
            ->between(1, 48)
            ->andReturnSelf()
            ->shouldReceive('PollGenerateReport')
            ->between(1, 48)
            ->andReturnSelf();

        $status = new \stdClass();
        $status->Status = $reportStatus;
        $status->ReportDownloadUrl = 'http://example.com/download.zip';

        $clientProxyMock->ReportRequestId = 'reportID';
        $clientProxyMock->ReportRequestStatus = $status;

        return $clientProxyMock;
    }

    /**
     * @return Mockery\MockInterface
     */
    private function getRequestNewAccessTokenMock()
    {
        $requestNewAccessTokenMock = Mockery::mock(OauthTokenService::class);
        $requestNewAccessTokenMock
            ->shouldReceive('refreshToken')
            ->with('clientId', 'clientSecret', 'redirectUri', AccessToken::class)
            ->once()
            ->andReturn(new AccessToken($this->accessToken, $this->refreshToken));

        return $requestNewAccessTokenMock;
    }

    /**
     * @return Mockery\MockInterface
     */
    private function getFileHelperMock()
    {
        $zipHelperMock = Mockery::mock(Helper\File::class);
        $zipHelperMock
            ->shouldReceive('getFile')
            ->andReturn('/tmp/report.zip')
            ->once()
            ->shouldReceive('unZip')
            ->with('/tmp/report.zip')
            ->andReturn([ASSETS_DIR . 'report.csv'])
            ->once();

        return $zipHelperMock;
    }

    /**
     * @return Mockery\MockInterface
     */
    private function getCsvHelperMock()
    {
        $lines = file(ASSETS_DIR . 'report.csv');
        $csvHelperMock = Mockery::mock(Helper\Csv::class);
        $csvHelperMock
            ->shouldReceive('removeLastLines')
            ->andReturn($lines)
            ->once()
            ->shouldReceive('fixDate')
            ->andReturn($lines)
            ->once();

        return $csvHelperMock;
    }

    private function getTimeHelperMock()
    {
        $timeHelperMock = Mockery::mock(Helper\Time::class);
        $timeHelperMock->shouldReceive('sleep')->andReturnNull();

        return $timeHelperMock;
    }

    /**
     * @param OauthTokenService $requestNewAccessToken
     * @param ClientProxy $clientProxy
     * @param Helper\File $fileHelper
     * @param Helper\Csv $csvHelper
     * @param Helper\Time $timeHelper
     *
     * @return Client
     */
    private function getApiClient(OauthTokenService $requestNewAccessToken, ClientProxy $clientProxy, Helper\File $fileHelper, Helper\Csv $csvHelper, Helper\Time $timeHelper)
    {
        $apiClient = new Client($requestNewAccessToken, $clientProxy, $fileHelper, $csvHelper, $timeHelper);
        $apiClient->setConfig(['cache_dir' => '/tmp']);
        $apiClient->setApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken');

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
