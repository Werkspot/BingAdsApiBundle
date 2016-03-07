<?php
namespace Werkspot\BingAdsApiBundle\Api;

use BingAds\Proxy\ClientProxy;
use BingAds\Reporting\PollGenerateReportRequest;
use BingAds\Reporting\ReportRequest;
use BingAds\Reporting\ReportTimePeriod;
use BingAds\Reporting\SubmitGenerateReportRequest;
use Exception;
use SoapFault;
use SoapVar;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Werkspot\BingAdsApiBundle\Api\Helper\Csv;
use Werkspot\BingAdsApiBundle\Api\Helper\File;
use Werkspot\BingAdsApiBundle\Api\Helper\Time;
use Werkspot\BingAdsApiBundle\Api\Report\ReportInterface;
use Werkspot\BingAdsApiBundle\Guzzle\OauthTokenService;
use Werkspot\BingAdsApiBundle\Model\AccessToken;
use Werkspot\BingAdsApiBundle\Model\ApiDetails;

class Client
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var ClientProxy
     */
    private $proxy;

    /**
     * @var array
     */
    public $report;

    /**
     * @var string
     */
    private $files;

    /**
     * @var OauthTokenService
     */
    private $oauthTokenService;

    /**
     * @var ApiDetails
     */
    private $apiDetails;

    /**
     * @var ClientProxy
     */
    private $clientProxy;

    /**
     * @var File
     */
    private $fileHelper;

    /**
     * @var Csv
     */
    private $csvHelper;

    /**
     * @var Time
     */
    private $timeHelper;

    /**
     * Client constructor.
     *
     * @param OauthTokenService $oauthTokenService
     * @param ApiDetails $apiDetails
     * @param ClientProxy $clientProxy
     * @param File $file
     * @param Csv $csv
     * @param Time $timeHelper
     */
    public function __construct(OauthTokenService $oauthTokenService, ApiDetails $apiDetails, ClientProxy $clientProxy, File $file, Csv $csv, Time $timeHelper)
    {
        $this->oauthTokenService = $oauthTokenService;
        $this->apiDetails = $apiDetails;
        $this->clientProxy = $clientProxy;
        $this->fileHelper = $file;
        $this->csvHelper = $csv;
        $this->timeHelper = $timeHelper;

        ini_set('soap.wsdl_cache_enabled', '0');
        ini_set('soap.wsdl_cache_ttl', '0');

        $this->fileName = 'report.zip';

        $this->report = [
            'GeoLocationPerformanceReport' => new Report\GeoLocationPerformanceReport(),
        ];
    }

    public function setApiDetails(ApiDetails $apiDetails)
    {
        $this->apiDetails = $apiDetails;
    }

    /**
     * Sets the configuration
     *
     * @param $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
        $this->config['cache_dir'] = $this->config['cache_dir'] . '/' . 'BingAdsApiBundle'; //<-- important for the cache clear function
        $this->config['csv']['fixHeader']['removeColumnHeader'] = true; //-- fix till i know how to do this
    }

    public function getRefreshToken()
    {
        return $this->apiDetails->getRefreshToken();
    }

    /**
     * @param array $columns
     * @param string $name
     * @param $timePeriod
     * @param null|string $fileLocation
     *
     * @return array|string
     */
    public function get(array $columns, $name = 'GeoLocationPerformanceReport', $timePeriod = ReportTimePeriod::LastWeek, $fileLocation = null)
    {
        $tokens = $this->oauthTokenService->refreshToken(
            $this->apiDetails->getClientId(),
            $this->apiDetails->getSecret(),
            $this->apiDetails->getRedirectUri(),
            new AccessToken(null, $this->apiDetails->getRefreshToken())
        );

        $accessToken = $tokens->getAccessToken();
        $this->apiDetails->setRefreshToken($tokens->getRefreshToken());

        $report = $this->report[$name];
        $report->setTimePeriod($timePeriod);
        $report->setColumns($columns);
        $reportRequest = $report->getRequest();
        $this->setProxy($report::WSDL, $accessToken);
        $files = $this->getFilesFromReportRequest($reportRequest, $name, "{$this->getCacheDir()}/{$this->fileName}", $report);

        if ($fileLocation !== null) {
            $this->moveFirstFile($fileLocation);

            return $fileLocation;
        } else {
            return $files;
        }
    }

    /**
     * @param string $wsdl
     * @param string $accessToken
     */
    private function setProxy($wsdl, $accessToken)
    {
        $this->proxy = $this->clientProxy->ConstructWithCredentials($wsdl, null, null, $this->apiDetails->getDevToken(), $accessToken);
    }

    /**
     * @return string
     */
    private function getCacheDir()
    {
        $fs = new Filesystem();
        if (!$fs->exists($this->config['cache_dir'])) {
            $fs->mkdir($this->config['cache_dir'], 0700);
        }

        return $this->config['cache_dir'];
    }

    /**
     * @param ReportRequest $reportRequest
     * @param string $name
     * @param string $downloadFile
     * @param ReportInterface $report
     *
     * @throws Exception
     *
     * @return array
     */
    private function getFilesFromReportRequest(ReportRequest $reportRequest, $name, $downloadFile, ReportInterface $report)
    {
        $reportRequestId = $this->submitGenerateReport($reportRequest, $name);
        $reportRequestStatus = $this->waitForStatus($reportRequestId);
        $reportDownloadUrl = $reportRequestStatus->ReportDownloadUrl;
        $zipFile = $this->fileHelper->getFile($reportDownloadUrl, $downloadFile);
        if ($zipFile !== false) {
            $this->files = $this->fileHelper->unZip($zipFile);
            $this->fixFile($report);
        }

        return $this->files;
    }

    /**
     * SubmitGenerateReport helper method calls the corresponding Bing Ads service operation
     * to request the report identifier. The identifier is used to check report generation status
     * before downloading the report.
     *
     * @param mixed  $report
     * @param string $name
     *
     * @return string ReportRequestId
     */
    private function submitGenerateReport($report, $name)
    {
        $request = new SubmitGenerateReportRequest();
        try {
            $request->ReportRequest = $this->getReportRequest($report, $name);

            return $this->proxy->GetService()->SubmitGenerateReport($request)->ReportRequestId;
        } catch (SoapFault $e) {
            $this->parseSoapFault($e);
        }
    }

    /**
     * @param mixed  $report
     * @param string $name
     *
     * @return SoapVar
     */
    private function getReportRequest($report, $name)
    {
        $name = "{$name}Request";

        return new SoapVar($report, SOAP_ENC_OBJECT, $name, $this->proxy->GetNamespace());
    }

    /**
     * Check if the report is ready for download
     * if not wait 10 sec and retry. (up to 6,5 hour)
     * After 30 tries check every 1 minute
     * After 34 tries check every 5 minutes
     * After 39 tries check every 15 minutes
     * After 43 tries check every 30 minutes
     *
     * @param string  $reportRequestId
     * @param int     $count
     * @param int     $maxCount
     * @param int     $sleep
     * @param bool $incrementTime
     *
     * @throws Exceptions\ReportRequestErrorException
     * @throws Exceptions\RequestTimeoutException
     *
     * @return string
     */
    private function waitForStatus($reportRequestId, $count = 1, $maxCount = 48, $sleep = 10, $incrementTime = true)
    {
        if ($count > $maxCount) {
            throw new Exceptions\RequestTimeoutException("The request is taking longer than expected.\nSave the report ID ({$reportRequestId}) and try again later.");
        }

        $reportRequestStatus = $this->pollGenerateReport($reportRequestId);
        if ($reportRequestStatus->Status == 'Pending') {
            ++$count;
            $this->timeHelper->sleep($sleep);
            if ($incrementTime) {
                switch ($count) {
                    case 31: // after 5 minutes
                        $sleep = (1 * 60);
                        break;
                    case 35: // after 10 minutes
                        $sleep = (5 * 60);
                        break;
                    case 40: // after 30 minutes
                        $sleep = (15 * 60);
                        break;
                    case 44: // after 1,5 hours
                        $sleep = (30 * 60);
                        break;
                }
            }
            $reportRequestStatus = $this->waitForStatus($reportRequestId, $count, $maxCount, $sleep, $incrementTime);
        }

        if ($reportRequestStatus->Status == 'Error') {
            throw new Exceptions\ReportRequestErrorException("The request failed. Try requesting the report later.\nIf the request continues to fail, contact support.", $reportRequestStatus->Status, $reportRequestId);
        }

        return $reportRequestStatus;
    }

    /**
     * Check the status of the report request. The guidance of how often to poll
     * for status is from every five to 15 minutes depending on the amount
     * of data being requested. For smaller reports, you can poll every couple
     * of minutes. You should stop polling and try again later if the request
     * is taking longer than an hour.
     *
     * @param string $reportRequestId
     *
     * @return string ReportRequestStatus
     */
    private function pollGenerateReport($reportRequestId)
    {
        $request = new PollGenerateReportRequest();
        $request->ReportRequestId = $reportRequestId;
        try {
            return $this->proxy->GetService()->PollGenerateReport($request)->ReportRequestStatus;
        } catch (SoapFault $e) {
            $this->parseSoapFault($e);
        }
    }

    /**
     * @param array|null $files
     *
     * @return self
     */
    private function fixFile(ReportInterface $report, array $files = null)
    {
        $files = (!$files) ? $this->files : $files;
        foreach ($files as $file) {
            $lines = file($file);
            $lines = $this->csvHelper->removeHeaders($lines, $this->config['csv']['fixHeader']['removeColumnHeader'], $report::FILE_HEADERS, $report::COLUMN_HEADERS);
            $lines = $this->csvHelper->removeLastLines($lines);
            $lines = $this->csvHelper->convertDateMDYtoYMD($lines);
            $fp = fopen($file, 'w');
            fwrite($fp, implode('', $lines));
            fclose($fp);
        }

        return $this;
    }

    /**
     * Move first file form array $this->files to the target location
     *
     * @param string $target
     *
     * @return self
     */
    private function moveFirstFile($target)
    {
        $fs = new Filesystem();
        $fs->rename($this->files[0], $target);

        return $this;
    }

    /**
     * Clear Bundle Cache directory
     *
     * @param bool $allFiles delete all files in bundles cache, if false deletes only extracted files ($this->files)
     *
     * @return self
     *
     * @codeCoverageIgnore
     */
    public function clearCache($allFiles = false)
    {
        $fileSystem = new Filesystem();

        if ($allFiles) {
            $finder = new Finder();
            $files = $finder->files()->in($this->config['cache_dir']);
        } else {
            $files = $this->files;
        }

        foreach ($files as $file) {
            $fileSystem->remove($file);
        }

        return $this;
    }

    /**
     * @param SoapFault $e
     *
     * @throws Exceptions\SoapInternalErrorException
     * @throws Exceptions\SoapInvalidCredentialsException
     * @throws Exceptions\SoapNoCompleteDataAvailableException
     * @throws Exceptions\SoapReportingServiceInvalidReportIdException
     * @throws Exceptions\SoapUnknownErrorException
     * @throws Exceptions\SoapUserIsNotAuthorizedException
     */
    private function parseSoapFault(SoapFault $e)
    {
        if (isset($e->detail->AdApiFaultDetail)) {
            $error = $e->detail->AdApiFaultDetail->Errors->AdApiError;
        } elseif (isset($e->detail->ApiFaultDetail)) {
            if (!empty($e->detail->ApiFaultDetail->BatchErrors)) {
                $error = $error = $e->detail->ApiFaultDetail->Errors->AdApiError;
            } elseif (!empty($e->detail->ApiFaultDetail->OperationErrors)) {
                $error = $e->detail->ApiFaultDetail->OperationErrors->OperationError;
            }
        }
        $errors = is_array($error) ? $error : ['error' => $error];
        foreach ($errors as $error) {
            switch ($error->Code) {
                case 0:
                    throw new Exceptions\SoapInternalErrorException($error->Message, $error->Code);
                case 105:
                    throw new Exceptions\SoapInvalidCredentialsException($error->Message, $error->Code);
                case 106:
                    throw new Exceptions\SoapUserIsNotAuthorizedException($error->Message, $error->Code);
                case 2004:
                    throw new Exceptions\SoapNoCompleteDataAvailableException($error->Message, $error->Code);
                case 2100:
                    throw new Exceptions\SoapReportingServiceInvalidReportIdException($error->Message, $error->Code);
                default:
                    $errorMessage = "[{$error->Code}]\n{$error->Message}";
                    throw new Exceptions\SoapUnknownErrorException($errorMessage, $error->Code);
            }
        }
    }
}
