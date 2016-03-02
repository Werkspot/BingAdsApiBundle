<?php

namespace Werkspot\BingAdsApiBundle\Api\Helper;

use GuzzleHttp\ClientInterface;
use Symfony\Component\Filesystem\Filesystem;
use Werkspot\BingAdsApiBundle\Guzzle\Exceptions\CurlException;
use Werkspot\BingAdsApiBundle\Guzzle\Exceptions\HttpStatusCodeException;

class Zip
{
    /**
     * @var ClientInterface
     */
    private $guzzleClient;

    public function __construct(ClientInterface $guzzleClient = null)
    {
        $this->guzzleClient = $guzzleClient;
    }

    /**
     *
     * @param string $url Url we want to download from
     * @param string $file local file we want to store the data including path (usually $this->cacheDir)
     *
     * @return string
     *
     * @throws CurlException
     * @throws HttpStatusCodeException
     * @throws \Exception
     */
    public function download($url, $file)
    {
        $this->guzzleClient->request('GET', $url, ['sink' => $file]);
        return $file;
    }

    /**
     *
     * @param string $file zipFile we want to open
     * @param bool $delete
     *
     * @return array of extracted files
     *
     * @throws \Exception
     */
    public function unZip($file, $delete = true)
    {
        $zipDir = dirname($file);
        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            throw new \Exception("Could not open file {$file}");
        }
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
        return $files;
    }
}
