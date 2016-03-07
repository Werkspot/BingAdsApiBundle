<?php
namespace Werkspot\BingAdsApiBundle\Api\Helper;

use Exception;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Filesystem\Filesystem;
use Werkspot\BingAdsApiBundle\Guzzle\Exceptions\CurlException;
use Werkspot\BingAdsApiBundle\Guzzle\Exceptions\HttpStatusCodeException;
use ZipArchive;

class File
{
    /**
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(ClientInterface $guzzleClient = null)
    {
        $this->guzzleClient = $guzzleClient;
        $this->filesystem = new Filesystem();
    }

    /**
     * @param $source
     * @param null|string $destination
     *
     * @throws Exception
     *
     * @return bool|string
     */
    public function getFile($source, $destination = null)
    {
        if (preg_match('/^((https?)\:\/\/)?([a-z0-9-.]*)\.([a-z]{2,255})(\:[0-9]{2,5})?(\/([a-z0-9+$_-]\.?)+)*\/?(\?[a-z+&$_.-][a-z0-9;:@&%=+\/$_.-]*)?(#[a-z_.-][a-z0-9+$_.-]*)?$/', $source)) {
            if ($destination === null) {
                throw new Exception('No file destination given.');
            }
            $destination = $this->download($source, $destination);
        } else {
            if ($destination !== null) {
                $this->filesystem->copy($source, $destination);
            } else {
                $destination = $source;
            }
        }

        if (!$this->filesystem->exists($destination)) {
            return false;
        }

        return $destination;
    }

    /**
     * @param string $url
     * @param string $destination
     *
     * @throws CurlException
     * @throws HttpStatusCodeException
     * @throws \Exception
     *
     * @return string
     */
    public function download($url, $destination)
    {
        $this->guzzleClient->request('GET', $url, ['sink' => $destination]);

        return $destination;
    }

    /**
     * @param string $file zipFile we want to open
     * @param null|string $extractTo
     * @param true|bool $delete
     *
     * @throws Exception
     *
     * @return array
     */
    public function unZip($file, $extractTo = null, $delete = true)
    {
        $zipDir = ($extractTo) ? $extractTo : dirname($file);
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new Exception("Could not open file {$file}");
        }
        $files = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $stat = $zip->statIndex($i);
            $files[] = "{$zipDir}/{$stat['name']}";
        }
        $zip->extractTo($zipDir);
        $zip->close();
        if ($delete) {
            $this->filesystem->remove($file);
        }

        return $files;
    }
}
