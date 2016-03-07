<?php

namespace Tests\Werkspot\BingAdsApiBundle\Api\Report;

use BingAds\Bulk\ReportTimePeriod;
use BingAds\Proxy\ClientProxy;
use BingAds\Reporting\AccountThroughAdGroupReportScope;
use BingAds\Reporting\GeoLocationPerformanceReportRequest;
use BingAds\Reporting\NonHourlyReportAggregation;
use BingAds\Reporting\ReportFormat;
use BingAds\Reporting\ReportTime;
use Mockery;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Filesystem;
use stdClass;
use SoapFault;
use Werkspot\BingAdsApiBundle\Api\Client;
use Werkspot\BingAdsApiBundle\Api\Exceptions;
use Werkspot\BingAdsApiBundle\Api\Helper;
use Werkspot\BingAdsApiBundle\Api\Report\GeoLocationPerformanceReport;
use Werkspot\BingAdsApiBundle\Guzzle\OauthTokenService;
use Werkspot\BingAdsApiBundle\Model\AccessToken;
use Werkspot\BingAdsApiBundle\Model\ApiDetails;

class GeoLocationPerformanceReportTest extends PHPUnit_Framework_TestCase
{
    const YESTERDAY = 'Yesterday';
    const ACCESS_TOKEN = '2ec09aeccaf634d982eec793037e37fe';
    const REFRESH_TOKEN = '0c59f7e609b0cc467067e39d523116ce';

    public function testGetRequest()
    {
        $expected = new GeoLocationPerformanceReportRequest();
        $expected->Format = ReportFormat::Csv;
        $expected->ReportName = GeoLocationPerformanceReport::NAME;
        $expected->ReturnOnlyCompleteData = true;
        $expected->Aggregation = NonHourlyReportAggregation::Daily;
        $expected->Scope = new AccountThroughAdGroupReportScope();
        $expected->Time = new ReportTime();
        $expected->Time->PredefinedTime = self::YESTERDAY;
        $expected->Columns = [];

        $report = new GeoLocationPerformanceReport();
        $report->setTimePeriod(self::YESTERDAY);
        $report->setColumns([]);
        $result = $report->getRequest();

        $this->assertEquals($expected, $result);
    }

    public function testSetAggregation()
    {
        $report = new GeoLocationPerformanceReport();

        $report->setTimePeriod(self::YESTERDAY);
        $report->setColumns([]);
        $result = $report->getRequest();
        $this->assertEquals(NonHourlyReportAggregation::Daily, $result->Aggregation);

        $report->setAggregation(NonHourlyReportAggregation::Monthly);
        $report->setTimePeriod(self::YESTERDAY);
        $report->setColumns([]);
        $result = $report->getRequest();

        $this->assertEquals(NonHourlyReportAggregation::Monthly, $result->Aggregation);
    }

    public function testGeoLocationPerformanceReportReturnsArrayWithCsv()
    {
        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            new ApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken'),
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
            new ApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken'),
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
            new ApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken'),
            $this->getClientProxyMock(),
            $this->getFileHelperMock(),
            $this->getCsvHelperMock(),
            $this->getTimeHelperMock()
        );

        $this->assertEquals('refreshToken', $apiClient->getRefreshToken());

        $apiClient->get(['TimePeriod', 'AccountName', 'AdGroupId'], 'GeoLocationPerformanceReport', ReportTimePeriod::LastWeek);
        $this->assertEquals(self::REFRESH_TOKEN, $apiClient->getRefreshToken());
    }

    public function testGeoLocationPerformanceReportTimeoutException()
    {
        $this->expectException(Exceptions\RequestTimeoutException::class);
        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            new ApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken'),
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
            new ApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken'),
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
            new ApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken'),
            $clientProxyMock,
            new Helper\File(),
            new Helper\Csv(),
            $this->getTimeHelperMock()
        );
        $apiClient->get(['TimePeriod', 'AccountName', 'AdGroupId'], 'GeoLocationPerformanceReport');
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
        $oauthTokenServiceMock = Mockery::mock(OauthTokenService::class);
        $oauthTokenServiceMock
            ->shouldReceive('refreshToken')
            ->with('clientId', 'clientSecret', 'redirectUri', AccessToken::class)
            ->once()
            ->andReturn(new AccessToken(self::ACCESS_TOKEN, self::REFRESH_TOKEN));

        return $oauthTokenServiceMock;
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
            ->shouldReceive('removeHeaders')
            ->andReturn($lines)
            ->once()
            ->shouldReceive('removeLastLines')
            ->andReturn($lines)
            ->once()
            ->shouldReceive('convertDateMDYtoYMD')
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
    private function getApiClient(OauthTokenService $requestNewAccessToken, ApiDetails $apiDetails, ClientProxy $clientProxy, Helper\File $fileHelper, Helper\Csv $csvHelper, Helper\Time $timeHelper)
    {
        $apiClient = new Client($requestNewAccessToken, $apiDetails, $clientProxy, $fileHelper, $csvHelper, $timeHelper);
        $apiClient->setConfig(['cache_dir' => '/tmp']);

        return $apiClient;
    }

    /**
     * @param $code
     * @return SoapFault
     */
    private function generateSoapFault($code)
    {
        $message = "an error message {$code}";
        $error = new stdClass();
        $error->Code = $code;
        $error->Message = $message;
        $exception = new SoapFault('Server', '');
        $exception->detail = new stdClass();
        $exception->detail->AdApiFaultDetail = new stdClass();
        $exception->detail->AdApiFaultDetail->Errors = new stdClass();
        $exception->detail->AdApiFaultDetail->Errors->AdApiError = [$error];

        return $exception;
    }
}
