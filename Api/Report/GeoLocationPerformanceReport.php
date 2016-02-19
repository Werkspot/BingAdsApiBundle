<?php

namespace Werkspot\BingAdsApiBundle\Api\Report;


use BingAds\Reporting\NonHourlyReportAggregation;
use BingAds\Reporting\GeoLocationPerformanceReportRequest;
use BingAds\Reporting\ReportFormat;
use BingAds\Reporting\AccountThroughAdGroupReportScope;
use BingAds\Reporting\ReportTime;

class GeoLocationPerformanceReport implements ReportInterface
{
    const NAME = 'GeoLocationPerformanceReportRequest';
    const WSDL = 'https://api.bingads.microsoft.com/Api/Advertiser/Reporting/V9/ReportingService.svc?singleWsdl';

    /**
     * {@inheritdoc}
     */
    public function getRequest($columns, $timePeriod)
    {
        $reportRequest = new GeoLocationPerformanceReportRequest();

        $reportRequest->Format = ReportFormat::Csv;
        $reportRequest->ReportName = self::NAME;
        $reportRequest->ReturnOnlyCompleteData = true;
        $reportRequest->Aggregation = NonHourlyReportAggregation::Daily;
        $reportRequest->Scope = new AccountThroughAdGroupReportScope();
        $reportRequest->Time = new ReportTime();
        $reportRequest->Time->PredefinedTime = $timePeriod;
        $reportRequest->Columns = $columns;

        return $reportRequest;
    }
}