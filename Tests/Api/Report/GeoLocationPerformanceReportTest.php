<?php

namespace Tests\Werkspot\BingAdsApiBundle\Tests\Api\Report;

use BingAds\Reporting\AccountThroughAdGroupReportScope;
use BingAds\Reporting\GeoLocationPerformanceReportRequest;
use BingAds\Reporting\NonHourlyReportAggregation;
use BingAds\Reporting\ReportFormat;
use BingAds\Reporting\ReportTime;
use Werkspot\BingAdsApiBundle\Api\Report\GeoLocationPerformanceReport;

class GeoLocationPerformanceReportTest extends \PHPUnit_Framework_TestCase
{
    const YESTERDAY = 'Yesterday';

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
        $result = $report->getRequest([], self::YESTERDAY);

        $this->assertEquals($expected, $result);
    }

    public function testSetAggregation()
    {
        $report = new GeoLocationPerformanceReport();

        $result = $report->getRequest([], self::YESTERDAY);
        $this->assertEquals(NonHourlyReportAggregation::Daily, $result->Aggregation);

        $report->setAggregation(NonHourlyReportAggregation::Monthly);
        $result = $report->getRequest([], self::YESTERDAY);

        $this->assertEquals(NonHourlyReportAggregation::Monthly, $result->Aggregation);
    }
}
