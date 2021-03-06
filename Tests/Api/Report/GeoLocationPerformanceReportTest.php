<?php
namespace Tests\Werkspot\BingAdsApiBundle\Api\Report;

use BingAds\Bulk\ReportTimePeriod;
use BingAds\Proxy\ClientProxy;
use BingAds\Reporting\AccountThroughAdGroupReportScope;
use BingAds\Reporting\GeoLocationPerformanceReportRequest;
use BingAds\Reporting\NonHourlyReportAggregation;
use BingAds\Reporting\ReportFormat;
use BingAds\Reporting\ReportTime;
use GuzzleHttp\Client as GuzzleClient;
use Mockery;
use PHPUnit_Framework_TestCase;
use SoapFault;
use stdClass;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
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

    const CACHE_DIR = '/tmp';

    const CSV_REPORT_PATH = 'report.csv';

    private static $LINES_IN_REPORT = ['something', 'else'];

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

    public function testGeoLocationPerformanceReportMoveFile()
    {
        $fileLocation = 'test.csv';

        $fileHelperMock = $this->getFileHelperMock();
        $fileHelperMock->shouldReceive('moveFirstFile')
            ->with([self::CSV_REPORT_PATH], $fileLocation)
            ->andReturn(self::CSV_REPORT_PATH)
            ->once();

        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            new ApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken'),
            $this->getClientProxyMock(),
            $fileHelperMock,
            $this->getCsvHelperMock(),
            $this->getTimeHelperMock()
        );

        $this->assertEquals('refreshToken', $apiClient->getRefreshToken());
        $apiClient->getReport('GeoLocationPerformanceReport', ['TimePeriod', 'AccountName', 'AdGroupId'], ReportTimePeriod::LastWeek, $fileLocation);
        $this->assertEquals(self::REFRESH_TOKEN, $apiClient->getRefreshToken());
    }

    /**
     * @expectedException \Werkspot\BingAdsApiBundle\Api\Exceptions\RequestTimeoutException
     */
    public function testGeoLocationPerformanceReportTimeoutException()
    {
        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            new ApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken'),
            $this->getClientProxyMock('Pending'),
            $this->getFileHelper(),
            new Helper\Csv(),
            $this->getTimeHelperMock()
        );
        $apiClient->getReport('GeoLocationPerformanceReport', ['TimePeriod', 'AccountName', 'AdGroupId'], ReportTimePeriod::LastMonth, self::CSV_REPORT_PATH);
    }

    /**
     * @expectedException \Werkspot\BingAdsApiBundle\Api\Exceptions\ReportRequestErrorException
     */
    public function testGeoLocationPerformanceReportRequestErrorException()
    {
        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            new ApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken'),
            $this->getClientProxyMock('Error'),
            $this->getFileHelper(),
            new Helper\Csv(),
            $this->getTimeHelperMock()
        );
        $apiClient->getReport('GeoLocationPerformanceReport', ['TimePeriod', 'AccountName', 'AdGroupId'], ReportTimePeriod::LastMonth, self::CSV_REPORT_PATH);
    }

    public function testClearCache()
    {
        $fileHelperMock = Mockery::mock(Helper\File::class);
        $fileHelperMock
            ->shouldReceive('clearCache')
            ->with(null)
            ->once()

            ->shouldReceive('clearCache')
            ->withAnyArgs()
            ->once();
        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            new ApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken'),
            new ClientProxy('example.com'),
            $fileHelperMock,
            new Helper\Csv(),
            new Helper\Time()
        );
        $apiClient->clearCache();
        $apiClient->clearCache(true);
    }

    /**
     * @dataProvider getReportNameData
     *
     * @expectedException \Werkspot\BingAdsApiBundle\Api\Exceptions\InvalidReportNameException
     */
    public function testThrowsExceptionOnEmptyReportName($name)
    {
        $apiClient = $this->getApiClient(
            $this->getRequestNewAccessTokenMock(),
            new ApiDetails('refreshToken', 'clientId', 'clientSecret', 'redirectUri', 'devToken'),
            new ClientProxy('example.com'),
            $this->getFileHelper(),
            new Helper\Csv(),
            new Helper\Time()
        );
        $apiClient->getReport($name, [], ReportTimePeriod::LastMonth, self::CSV_REPORT_PATH);
    }

    public function getReportNameData()
    {
        return [
            'Empty string' => [''],
            'NULL' => [null],
        ];
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
            $this->getFileHelper(),
            new Helper\Csv(),
            $this->getTimeHelperMock()
        );
        $apiClient->getReport('GeoLocationPerformanceReport', ['TimePeriod', 'AccountName', 'AdGroupId'], ReportTimePeriod::LastMonth, self::CSV_REPORT_PATH);
    }
    /**
     * @return Mockery\MockInterface|ClientProxy
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
     * @return Mockery\MockInterface|OauthTokenService
     */
    private function getRequestNewAccessTokenMock()
    {
        $oauthTokenServiceMock = Mockery::mock(OauthTokenService::class);
        $oauthTokenServiceMock
            ->shouldReceive('refreshToken')
            ->with('clientId', 'clientSecret', 'redirectUri', AccessToken::class)
            ->andReturn(new AccessToken(self::ACCESS_TOKEN, self::REFRESH_TOKEN));

        return $oauthTokenServiceMock;
    }

    /**
     * @return Mockery\MockInterface|Helper\File
     */
    private function getFileHelperMock()
    {
        $zipHelperMock = Mockery::mock(Helper\File::class);
        $zipHelperMock
            ->shouldReceive('createDirIfNotExists')
            ->once()

            ->shouldReceive('copyFile')
            ->andReturn('/tmp/report.zip')
            ->once()

            ->shouldReceive('unZip')
            ->with('/tmp/report.zip')
            ->andReturn([self::CSV_REPORT_PATH])
            ->once()

            ->shouldReceive('isHealthyZipFile')
            ->with('/tmp/report.zip')
            ->andReturn(true)
            ->once()

            ->shouldReceive('readFileLinesIntoArray')
            ->with(self::CSV_REPORT_PATH)
            ->andReturn(self::$LINES_IN_REPORT)
            ->once()

            ->shouldReceive('writeLinesToFile')
            ->with(self::$LINES_IN_REPORT, self::CSV_REPORT_PATH)
            ->once();

        return $zipHelperMock;
    }

    /**
     * @return Mockery\MockInterface|Helper\Csv
     */
    private function getCsvHelperMock()
    {
        $lines = self::$LINES_IN_REPORT;
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

    /**
     * @return Mockery\MockInterface|Helper\Time
     */
    private function getTimeHelperMock()
    {
        $timeHelperMock = Mockery::mock(Helper\Time::class);
        $timeHelperMock->shouldReceive('sleep')->andReturnNull();

        return $timeHelperMock;
    }

    /**
     * @param OauthTokenService $requestNewAccessToken
     * @param ApiDetails $apiDetails
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
        $apiClient->setConfig(['cache_dir' => self::CACHE_DIR]);

        return $apiClient;
    }

    /**
     * @param $code
     *
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

    /**
     * @return Helper\File
     */
    private function getFileHelper()
    {
        return  new Helper\File(new GuzzleClient(), new Filesystem(), new Finder());
    }
}
