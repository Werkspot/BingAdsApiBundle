<?php
namespace Tests\Werkspot\BingAdsApiBundle\Api\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Werkspot\BingAdsApiBundle\Api\Exceptions\NoFileDestinationException;
use Werkspot\BingAdsApiBundle\Api\Helper\File;

class FileTest extends PHPUnit_Framework_TestCase
{
    private $fileSystem;

    /**
     * FileTest constructor.
     */
    public function __construct()
    {
        $this->fileSystem = new Filesystem();
    }

    public function testGetFile()
    {
        $existingFile = ASSETS_DIR . 'report.csv';
        $onlineFile = 'http://google.com/test.txt';
        $file = ASSETS_DIR . 'example.txt';

        $mock = new MockHandler([new Response(200, [])]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $fileHelper = new File($client);

        $result = $fileHelper->copyFile($existingFile);
        $this->assertEquals($existingFile, $result);

        $result = $fileHelper->copyFile($existingFile, $file);
        $this->assertEquals($file, $result);
        $this->fileSystem->remove($file);

        $result = $fileHelper->copyFile($onlineFile, $file);
        $this->assertEquals($file, $result);

        $this->fileSystem->remove($file);

    }

    /**
     * @expectedException \Werkspot\BingAdsApiBundle\Api\Exceptions\FileNotCopiedException
     */
    public function testGetNonExistingFile()
    {
        $mock = new MockHandler([new Response(200, [])]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $fileHelper = new File($client);

        $nonExistingFile = ASSETS_DIR . 'iDoNotExist.txt';
        $result = $fileHelper->copyFile($nonExistingFile);
        $this->assertFalse($result);
    }

    public function testDownload()
    {
        $url = 'http://example.com';
        $file = ASSETS_DIR . 'example.txt';

        $clientMock = Mockery::mock(Client::class);
        $clientMock
            ->shouldReceive('request')
            ->with('GET', $url, ['sink' => $file])
            ->once()
            ->andReturn(new Response(200, [], 'test'));

        $fileHelper = new File($clientMock);
        $result = $fileHelper->download($url, $file);

        $this->assertEquals($file, $result);
    }

    /**
     * @expectedException \Werkspot\BingAdsApiBundle\Api\Exceptions\NoFileDestinationException
     */
    public function testDownloadThrowsException()
    {
        $url = 'http://example.com';

        $fileHelper = new File(new Client());
        $fileHelper->copyFile($url);
    }

    public function testIsZipFile()
    {
        $fileHelper = new File(new Client());
        $this->assertFalse($fileHelper->isHealthyZipFile(ASSETS_DIR . 'example.txt'));
        $this->assertFalse($fileHelper->isHealthyZipFile(ASSETS_DIR . 'corrupt.zip'));
        $this->assertTrue($fileHelper->isHealthyZipFile(ASSETS_DIR . 'report.zip'));
    }

    public function testUnZip()
    {
        $file = ASSETS_DIR . 'test.zip';

        $this->fileSystem->copy(ASSETS_DIR . 'report.zip', $file);

        $fileHelper = new File();
        $files = $fileHelper->unZip($file);

        $this->assertEquals($files, [ASSETS_DIR . '0039202.csv']);

        //-- Remove the files created by this test
        foreach ($files as $file) {
            $this->fileSystem->remove($file);
        }
    }

    /**
     * @expectedException \Exception
     */
    public function testCorruptUnZip()
    {
        $file = ASSETS_DIR . 'corrupt.zip';
        $fileHelper = new File();
        $fileHelper->unZip($file, null, false);
    }
}
