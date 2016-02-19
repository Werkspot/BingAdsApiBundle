<?php

namespace Werkspot\BingAdsApiBundle\Api;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use BingAds\Reporting\SubmitGenerateReportRequest;
use BingAds\Reporting\PollGenerateReportRequest;
use BingAds\Proxy\ClientProxy;
use SoapVar;
use Werkspot\BingAdsApiBundle\Api\Report\ReportInterface;
use Werkspot\BingAdsApiBundle\Guzzle\RequestNewAccessToken;

class Client
{

    /**
     * The settings for the api
     *
     * @var array
     */
    private $config = [];

    /**
     * @var string
     */
    private $fileName;

    /**
     * The refreshToken for the api
     *
     * @var ClientProxy
     */
    private $proxy;

    /**
     * The refreshToken for the api
     *
     * @var string
     */
    public $report;

    /**
     * Array of extracted files
     *
     * @var string
     */
    private $files;

    /**
     * @var RequestNewAccessToken
     */
    private $requestNewAccessToken;


    public function __construct(RequestNewAccessToken $requestNewAccessToken)
    {
        $this->requestNewAccessToken = $requestNewAccessToken;

        ini_set("soap.wsdl_cache_enabled", "0");
        ini_set("soap.wsdl_cache_ttl", "0");

        $this->fileName = "report.zip";

        $this->report = [
            'GeoLocationPerformanceReport' => new Report\GeoLocationPerformanceReport(),
        ];

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
    }

    /**
     * @param array $columns
     * @param string $name
     * @param $timePeriod
     * @param null $fileLocation
     *
     * @return array|string
     */
    public function get(array $columns, $name = 'GeoLocationPerformanceReport', $timePeriod = ReportTimePeriod::LastWeek, $fileLocation = null)
    {
        $accessToken = $this->requestNewAccessToken->get(
            $this->config['api_client_id'],
            $this->config['api_secret'],
            $this->config['redirect_uri'],
            $this->config['refresh_token']
        );

        $report = $this->report[$name];
        $reportRequest = $report->getRequest($columns, $timePeriod);
        $this->setProxy($report::WSDL, $accessToken);

        $files = $this->getFilesFromReportRequest($reportRequest, $name, "{$this->getCacheDir()}/{$this->fileName}");

        if ($fileLocation) {
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
        $this->proxy = ClientProxy::ConstructWithCredentials($wsdl, null, null, $this->config['dev_token'], $accessToken);
    }

    /**
     * Get the directory for the bundles cache
     *
     * @return string
     */
    public function getCacheDir()
    {
        $fs = new Filesystem();
        if (!$fs->exists($this->config['cache_dir'])) {
            $fs->mkdir($this->config['cache_dir'], 0700);
        }

        return $this->config['cache_dir'];
    }

    /**
     * @param $reportRequest
     * @param $name
     * @param $downloadFile
     *
     * @return string
     *§
     * @throws \Exception
     */
    private function getFilesFromReportRequest($reportRequest, $name, $downloadFile)
    {
        $reportRequestId = $this->submitGenerateReport($reportRequest, $name);
        $reportRequestStatus = $this->waitForStatus($reportRequestId);
        $reportDownloadUrl = $reportRequestStatus->ReportDownloadUrl;
        $zipFile = $this->downloadFile($reportDownloadUrl, $downloadFile);
        $this->openZipFile($zipFile);
        $this->removeLastLineFromFiles();

        return $this->files;
    }

    /**
     *
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
        $request->ReportRequest = $this->getReportRequest($report, $name);

        return $this->proxy->GetService()->SubmitGenerateReport($request)->ReportRequestId;
    }

    /**
     *
     * @param mixed  $report
     * @param string $name
     *
     * @return SoapVar
     */
    private function getReportRequest($report, $name)
    {
        $name = "{$name}Request";
        return new  SoapVar($report, SOAP_ENC_OBJECT, $name, $this->proxy->GetNamespace());
    }

    /**
     *
     * Check if the report is ready for download
     * if not wait 10 sec and retry (up to 1 hour)
     *
     * @param string $reportRequestId
     * @param int $count
     * @param int $maxCount
     * @param int $sleep
     *
     * @return string
     *
     * @throws \Exception
     */
    private function waitForStatus($reportRequestId, $count = 1, $maxCount = 360, $sleep = 10)
    {
        if ($count > $maxCount) {
            throw new \Exception("The request is taking longer than expected.\nSave the report ID ({$reportRequestId}) and try again later.");
        }

        $reportRequestStatus = $this->pollGenerateReport($reportRequestId);
        if ($reportRequestStatus->Status == "Pending") {
            $count++;
            sleep($sleep);
            $reportRequestStatus = $this->waitForStatus($reportRequestId, $count, $maxCount, $sleep);
        }

        if ($reportRequestStatus->Status == "Error") {
            throw new \Exception("The request failed. Try requesting the report later.\nIf the request continues to fail, contact support.");
        }

        return $reportRequestStatus;

    }

    /**
     *
     * Check the status of the report request. The guidance of how often to poll
     * for status is from every five to 15 minutes depending on the amount
     * of data being requested. For smaller reports, you can poll every couple
     * of minutes. You should stop polling and try again later if the request
     * is taking longer than an hour.
     *
     * @param $reportRequestId
     *
     * @return string ReportRequestStatus
     */
    private function pollGenerateReport($reportRequestId)
    {
        $request = new PollGenerateReportRequest();
        $request->ReportRequestId = $reportRequestId;

        return $this->proxy->GetService()->PollGenerateReport($request)->ReportRequestStatus;
    }

    /**
     *
     * @param string $url Url we want to download from
     * @param string $localFile local file we want to store the data including path (usually $this->cacheDir)
     *
     * @return string $localFile
     */
    private function downloadFile($url, $localFile)
    {
        file_put_contents($localFile, fopen($url, 'r'));
        return $localFile;
    }

    /**
     * Open zip file
     *
     * @param string $file zipFile we want to open
     *
     * @return array of extracted files
     */
    private function openZipFile($file, $delete = true)
    {
        $zipDir = dirname($file);
        $zip = new \ZipArchive();
        $zip->open($file);
        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $files[] = "{$zipDir}/{$stat['name']}";
        }
        $zip->extractTo($zipDir);
        $zip->close();
        if ($delete) {
            $fs = new Filesystem();
            $fs->remove($file);
        }
        $this->files = $files;

        return $this;
    }

    /**
     *
     * @param null $files
     * @param int $noOfLinesToRemove
     *
     * @return self
     */
    private function removeLastLineFromFiles($files = null, $noOfLinesToRemove = 1)
    {
        $files = (!$files) ? $this->files : $files;
        foreach ($files as $file) {
            $lines = file($file);
            $lastLine = sizeof($lines) - $noOfLinesToRemove;
            unset($lines[$lastLine]);

            $fp = fopen($file, 'w');
            fwrite($fp, implode('', $lines));
            fclose($fp);
        }

        return $this;
    }

    /**
     *
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

}
