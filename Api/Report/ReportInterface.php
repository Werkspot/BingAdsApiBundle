<?php

namespace Werkspot\BingAdsApiBundle\Api\Report;

interface ReportInterface
{
    /**
     * @param array   $columns
     * @param string  $timePeriod (See BingAds SDK documentation)
     *
     * @return ReportRequest
     */
    public function getRequest(array $columns, $timePeriod);
}
