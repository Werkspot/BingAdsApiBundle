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
     * {@inheritdoc}
     */
    public function getRequest(array $columns, $timePeriod)
    {
        $this->reportRequest->Time->PredefinedTime = $timePeriod;
        $this->reportRequest->Columns = $columns;

        return $this->reportRequest;
    }

    /**
     * @param string $aggregation (See BingAds SDK documentation)
     *
     * @return $this
     */
    public function setAggregation($aggregation)
    {
        $this->reportRequest->Aggregation = $aggregation;

        return $this;
    }
}
