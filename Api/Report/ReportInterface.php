<?php
namespace Werkspot\BingAdsApiBundle\Api\Report;

interface ReportInterface
{
    /**
     * @param string $format (See BingAds SDK documentation)
     */
    public function setFormat($format);

    /**
     * @param bool $returnOnlyCompleteData
    */
    public function setReturnOnlyCompleteData($returnOnlyCompleteData);

    /**
    * @param string $language (See BingAds SDK documentation)
    */
    public function setReportLanguage($language);

    /**
     * @return mixed
     */
    public function getRequest();
}
