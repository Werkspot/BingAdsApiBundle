<?php

namespace Werkspot\BingAdsApiBundle\Api\Report;

interface ReportInterface
{

    /**
     *
     * @param array   $columns
     * @param string  $timePeriod
     *
     * @return ReportRequest
     */
    public function getRequest(array $columns, $timePeriod);
}
