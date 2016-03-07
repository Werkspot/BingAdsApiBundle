<?php
namespace Werkspot\BingAdsApiBundle\Api\Exceptions;

use Exception;

/**
 * @codeCoverageIgnore
 */
class ReportRequestErrorException extends Exception
{
    /**
     * @var string
     */
    private $reportRequestStatus;

    /**
     * @var string
     */
    private $reportRequestId;

    /**
     * @param string $message
     * @param string $reportRequestStatus
     * @param string $reportRequestId
     */
    public function __construct($message, $reportRequestStatus, $reportRequestId)
    {
        $this->reportRequestStatus = $reportRequestStatus;
        $this->reportRequestId = $reportRequestId;

        parent::__construct($message);
    }

    /**
     * @return string
     */
    public function getReportRequestStatus()
    {
        return $this->reportRequestStatus;
    }

    /**
     * @return string
     */
    public function getReportRequestId()
    {
        return $this->reportRequestId;
    }
}
