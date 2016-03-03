<?php

namespace Werkspot\BingAdsApiBundle\Api\Helper;

use GuzzleHttp\ClientInterface;
use Symfony\Component\Filesystem\FileSystem;
use Werkspot\BingAdsApiBundle\Guzzle\Exceptions\CurlException;
use Werkspot\BingAdsApiBundle\Guzzle\Exceptions\HttpStatusCodeException;

class File
{
    /**
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @var FileSystem
     */
    private $fileSystem;

    public function __construct(ClientInterface $guzzleClient = null)
    {
        $this->guzzleClient = $guzzleClient;
        $this->fileSystem = new FileSystem();

    }

    /**
     * @param $source
     * @param null|string $destination
     *
     * @return bool|string
     *
     * @throws \Exception
     */
    public function getFile($source, $destination = null)
    {
        if (preg_match('/^((https?)\:\/\/)?([a-z0-9-.]*)\.([a-z]{2,255})(\:[0-9]{2,5})?(\/([a-z0-9+$_-]\.?)+)*\/?(\?[a-z+&$_.-][a-z0-9;:@&%=+\/$_.-]*)?(#[a-z_.-][a-z0-9+$_.-]*)?$/', $source)) {
            if (!$destination) {
                throw new \Exception("No file destination given.");
            }
            $destination = $this->download($source, $destination);
        } else {
            if ($destination) {
                $this->fileSystem->copy($source, $destination);
            } else {
                $destination = $source;
            }
        }

        if (!$this->fileSystem->exists($destination)) {
            return false;
        }
        return $destination;
    }

    /**
     *
     * @param string $url Url we want to download from
     * @param string $destination local file we want to store the data including path (usually $this->cacheDir)
     *
     * @return string
     *
     * @throws CurlException
     * @throws HttpStatusCodeException
     * @throws \Exception
     */
    public function download($url, $destination)
    {
        $this->guzzleClient->request('GET', $url, ['sink' => $destination]);
        return $destination;
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
            $this->fileSystem->remove($file);
        }
        return $files;
    }
}
