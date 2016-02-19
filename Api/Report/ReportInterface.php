<?php

namespace Werkspot\BingAdsApiBundle\Api\Report;

interface ReportInterface
{
    /**
     *
     * @param $columns
     * @param $timePeriod
     *
     * @return GeoLocationPerformanceReportRequest
     */
    public function getRequest($columns, $timePeriod);
}