<?php
namespace Werkspot\BingAdsApiBundle\Api\Report;

use BingAds\Reporting\ReportRequest;

class BaseReport implements ReportInterface
{
    const WSDL = 'https://api.bingads.microsoft.com/Api/Advertiser/Reporting/V9/ReportingService.svc?singleWsdl';
    const FILE_HEADERS = 10;
    const COLUMN_HEADERS = 1;

    protected $reportRequest;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->createReportRequest();
    }

    /**
     * @param $format
     *
     * @return $this
     */
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

    /**
     * @param string $language
     *
     * @return $this
     */
    public function setReportLanguage($language)
    {
        $this->reportRequest->Language = $language;

        return $this;
    }

    protected function createReportRequest()
    {
        $this->reportRequest = new ReportRequest();
    }

    /**
     * @param array|null $columns
     * @param string|null $timePeriod (See BingAds SDK documentation)
     *
     * @return ReportRequest
     */
    public function getRequest(array $columns = null, $timePeriod = null)
    {
        return $this->reportRequest;
    }
}
