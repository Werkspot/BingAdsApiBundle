<?php
namespace Werkspot\BingAdsApiBundle\Api\Helper;

use Exception;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Werkspot\BingAdsApiBundle\Api\Exceptions\FileNotCopiedException;
use Werkspot\BingAdsApiBundle\Api\Exceptions\NoFileDestinationException;
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

    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var ZipArchive
     */
    private $zipArchive;

    public function __construct(ClientInterface $guzzleClient, Filesystem $fileSystem, Finder $finder)
    {
        $this->guzzleClient = $guzzleClient;
        $this->filesystem = $fileSystem;
        $this->finder = $finder;
        $this->zipArchive = new ZipArchive();
    }

    /**
     * @param $source
     * @param null|string $destination
     *
     * @throws NoFileDestinationException|FileNotCopiedException
     *
     * @return string
     */
    public function copyFile($source, $destination = null)
    {
        if (preg_match('/^http(s?):\/\//', $source)) {
            if ($destination === null) {
                throw new NoFileDestinationException();
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
            throw new FileNotCopiedException();
        }

        return $destination;
    }

    /**
     * @param string $file
     *
     * @return bool
     */
    public function isHealthyZipFile($file)
    {
        $zipStatus = $this->zipArchive->open($file, ZipArchive::CHECKCONS);
        if ($zipStatus === ZipArchive::ER_OK || $zipStatus === true) {
            $this->zipArchive->close();
            $status = true;
        } else {
            $status = false;
        }

        return $status;
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

        if ($this->zipArchive->open($file) !== true) {
            throw new Exception("Could not open file {$file}");
        }
        $files = [];
        for ($i = 0; $i < $this->zipArchive->numFiles; ++$i) {
            $stat = $this->zipArchive->statIndex($i);
            $files[] = "{$zipDir}/{$stat['name']}";
        }
        $this->zipArchive->extractTo($zipDir);
        $this->zipArchive->close();
        if ($delete) {
            $this->filesystem->remove($file);
        }

        return $files;
    }

    /**
     * @param string $file
     * @return string[]
     */
    public function readFileLinesIntoArray($file)
    {
        return file($file);
    }

    /**
     * @param string[] $lines
     * @param string $file
     */
    public function writeLinesToFile($lines, $file)
    {
        $fp = fopen($file, 'w');
        fwrite($fp, implode('', $lines));
        fclose($fp);
    }

    /**
     * @param string|string[] $cache
     */
    public function clearCache($cache)
    {
        if (is_array($cache)) {
            foreach ($cache as $file) {
                $this->removeFile($file);
            }
        } elseif (is_dir($cache)) {
            foreach ($this->finder->files()->in($cache) as $file) {
                $this->removeFile($file);
            }
        } else {
            $this->removeFile($cache);
        }
    }

    /**
     * @param string $file
     */
    private function removeFile($file)
    {
        $this->filesystem->remove($file);
    }

    /**
     * @param string[] $files
     * @param string $target
     */
    public function moveFirstFile(array $files, $target)
    {
        $this->filesystem->rename($files[0], $target);
    }

    public function createDirIfNotExists($path)
    {
        if (!$this->filesystem->exists($path)) {
            $this->filesystem->mkdir($path, 0700);
        }
    }
}
