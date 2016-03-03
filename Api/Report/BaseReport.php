<?php

namespace Werkspot\BingAdsApiBundle\Api\Report;

use BingAds\Reporting\ReportRequest;

class BaseReport implements ReportInterface
{
    const WSDL = 'https://api.bingads.microsoft.com/Api/Advertiser/Reporting/V9/ReportingService.svc?singleWsdl';

    protected $reportRequest;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->createReportRequest();
    }

    public function setFormat($format)
    {
        $this->reportRequest->Format = $format;

        return $this;
    }

    /**
     * @param bool $returnOnlyCompleteData
     *
     * @return $this
     */
    public function setReturnOnlyCompleteData($returnOnlyCompleteData)
    {
        $this->reportRequest->ReturnOnlyCompleteData = $returnOnlyCompleteData;

        return $this;
    }

    public function setReportLanguage($language)
    {
        $this->reportRequest->Language = $language;
    }

    protected function createReportRequest()
    {
        $this->reportRequest = new ReportRequest();
    }

    public function getRequest(array $columns = null, $timePeriod = null)
    {
        return $this->reportRequest;
    }
}
