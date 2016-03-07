<?php
namespace Werkspot\BingAdsApiBundle\Api\Report;

use BingAds\Reporting\AccountThroughAdGroupReportScope;
use BingAds\Reporting\GeoLocationPerformanceReportRequest;
use BingAds\Reporting\NonHourlyReportAggregation;
use BingAds\Reporting\ReportFormat;
use BingAds\Reporting\ReportTime;

class GeoLocationPerformanceReport extends BaseReport
{
    const NAME = 'GeoLocationPerformanceReportRequest';

    /**
     * @var GeoLocationPerformanceReportRequest
     */
    protected $reportRequest;

    protected function createReportRequest()
    {
        $this->reportRequest = new GeoLocationPerformanceReportRequest();
        $this->reportRequest->Format = ReportFormat::Csv;
        $this->reportRequest->ReportName = self::NAME;
        $this->reportRequest->ReturnOnlyCompleteData = true;
        $this->reportRequest->Aggregation = NonHourlyReportAggregation::Daily;
        $this->reportRequest->Scope = new AccountThroughAdGroupReportScope();
        $this->reportRequest->Time = new ReportTime();
    }

    /**
     * @param string $aggregation (See BingAds SDK documentation)
     */
    public function setAggregation($aggregation)
    {
        $this->reportRequest->Aggregation = $aggregation;
    }

    /**
     * @param array $columns
     */
    public function setColumns(array $columns)
    {
        $this->reportRequest->Columns = $columns;
    }

    /**
     * @param string $timePeriod (See BingAds SDK documentation)
     */
    public function setTimePeriod($timePeriod)
    {
        $this->reportRequest->Time->PredefinedTime = $timePeriod;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        return $this->reportRequest;
    }
}
