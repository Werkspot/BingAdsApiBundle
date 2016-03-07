<?php
namespace Werkspot\BingAdsApiBundle\Api\Report;

use BingAds\Reporting\ReportRequest;

class BaseReport implements ReportInterface
{
    const WSDL = 'https://api.bingads.microsoft.com/Api/Advertiser/Reporting/V9/ReportingService.svc?singleWsdl';
    const FILE_HEADERS = 10;
    const COLUMN_HEADERS = 1;

    /**
     * @var ReportRequest
     */
    protected $reportRequest;

    public function __construct()
    {
        $this->createReportRequest();
    }

    protected function createReportRequest()
    {
        $this->reportRequest = new ReportRequest();
    }

    /**
     * {@inheritdoc}
     */
    public function setFormat($format)
    {
        $this->reportRequest->Format = $format;
    }

    /**
     * {@inheritdoc}
     */
    public function setReturnOnlyCompleteData($returnOnlyCompleteData)
    {
        $this->reportRequest->ReturnOnlyCompleteData = $returnOnlyCompleteData;
    }

    /**
     * {@inheritdoc}
     */
    public function setReportLanguage($language)
    {
        $this->reportRequest->Language = $language;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        return $this->reportRequest;
    }
}
