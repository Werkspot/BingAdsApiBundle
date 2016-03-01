<?php


namespace Werkspot\BingAdsApiBundle\Api\Helper;

use Symfony\Component\Filesystem\Filesystem;
use Werkspot\BingAdsApiBundle\Guzzle\Exceptions\CurlException;
use Werkspot\BingAdsApiBundle\Guzzle\Exceptions\HttpStatusCodeException;

class Zip
{
    /**
     *
     * @param string $url Url we want to download from
     * @param string $file local file we want to store the data including path (usually $this->cacheDir)
     *
     * @return string $localFile
     *
     * @throws CurlException
     * @throws HttpStatusCodeException
     * @throws \Exception
     */
    public function download($url, $file)
    {
        $fp = fopen($file, 'w+');

        if ($fp === false) {
            throw new \Exception('Could not open: ' . $file);
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_exec($ch);

        if (curl_errno($ch)) {
            throw new CurlException(curl_error($ch), curl_errno($ch));
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($statusCode <> 200) {
            echo "Status Code: " . $statusCode;
            throw new HttpStatusCodeException("", $statusCode);
        }


        //file_put_contents($file, fopen($url, 'r')); //-- if need to change this code write test for exception
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
