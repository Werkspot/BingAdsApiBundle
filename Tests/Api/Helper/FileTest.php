<?php
namespace Tests\Werkspot\BingAdsApiBundle\Api\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Werkspot\BingAdsApiBundle\Api\Helper\File;

class FileTest extends PHPUnit_Framework_TestCase
{
    public function testGetFile()
    {
        $fileSystem = $this->getFileSystem();
        $existingFile = ASSETS_DIR . 'report.csv';
        $onlineFile = 'http://google.com/test.txt';
        $file = ASSETS_DIR . 'example.txt';

        $mock = new MockHandler([new Response(200, [])]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $fileHelper = $this->getFileHelper($client);

        $result = $fileHelper->copyFile($existingFile);
        $this->assertEquals($existingFile, $result);

        $result = $fileHelper->copyFile($existingFile, $file);
        $this->assertEquals($file, $result);
        $fileSystem->remove($file);

        $result = $fileHelper->copyFile($onlineFile, $file);
        $this->assertEquals($file, $result);

        $fileSystem->remove($file);
    }

    /**
     * @expectedException \Werkspot\BingAdsApiBundle\Api\Exceptions\FileNotCopiedException
     */
    public function testGetNonExistingFile()
    {
        $mock = new MockHandler([new Response(200, [])]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $fileHelper = $this->getFileHelper($client);

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

        $fileHelper = $this->getFileHelper($clientMock);
        $result = $fileHelper->download($url, $file);

        $this->assertEquals($file, $result);
    }

    /**
     * @expectedException \Werkspot\BingAdsApiBundle\Api\Exceptions\NoFileDestinationException
     */
    public function testDownloadThrowsException()
    {
        $url = 'http://example.com';

        $fileHelper = $this->getFileHelper();
        $fileHelper->copyFile($url);
    }

    public function testIsZipFile()
    {
        $fileHelper = $this->getFileHelper();
        $this->assertFalse($fileHelper->isHealthyZipFile(ASSETS_DIR . 'example.txt'));
        $this->assertFalse($fileHelper->isHealthyZipFile(ASSETS_DIR . 'corrupt.zip'));
        $this->assertTrue($fileHelper->isHealthyZipFile(ASSETS_DIR . 'report.zip'));
    }

    public function testUnZip()
    {
        $fileSystem = $this->getFileSystem();

        $file = ASSETS_DIR . 'test.zip';

        $fileSystem->copy(ASSETS_DIR . 'report.zip', $file);

        $fileHelper = $this->getFileHelper();
        $files = $fileHelper->unZip($file);

        $this->assertEquals($files, [ASSETS_DIR . '0039202.csv']);

        //-- Remove the files created by this test
        foreach ($files as $file) {
            $fileSystem->remove($file);
        }
    }

    /**
     * @expectedException \Exception
     */
    public function testCorruptUnZip()
    {
        $file = ASSETS_DIR . 'corrupt.zip';
        $fileHelper = $this->getFileHelper();
        $fileHelper->unZip($file, null, false);
    }

    /**
     * @dataProvider getTestClearCacheData
     *
     * @param string $file
     * @param int $removeTimes
     */
    public function testClearCache($file, $removeTimes)
    {
        $fileSystemMock = Mockery::mock(Filesystem::class);
        $fileSystemMock
            ->shouldReceive('remove')
            ->times($removeTimes);

        $fileHelper = $this->getFileHelper(null, $fileSystemMock);
        $fileHelper->clearCache($file);
    }

    public function getTestClearCacheData()
    {
        return [
            'string - file' => [
                'file' => '/tmp/someFile.txt',
                'removeTimes' => 1
            ],
            'array - files' => [
                'file' => ['/tmp/someFile1.txt', '/tmp/someFile2.txt','/tmp/someFile3.txt',],
                'removeTimes' => 3
            ],
        ];
    }

    public function testClearCacheDir()
    {
        $path = '/tmp/';
        $numberOfFiles = count((new Finder())->files()->in($path));
        $fileSystemMock = Mockery::mock(Filesystem::class);
        $fileSystemMock
            ->shouldReceive('remove')
            ->times($numberOfFiles);

        $fileHelper = $this->getFileHelper(null, $fileSystemMock);
        $fileHelper->clearCache($path);
    }

    public function testMoveFirstFile()
    {
        $file = '/tmp/newFile.txt';
        $arrayFiles = ['/tmp/oldFile.txt'];
        $fileSystemMock = Mockery::mock(Filesystem::class);
        $fileSystemMock
            ->shouldReceive('rename')
            ->withArgs([$arrayFiles[0], $file])
            ->andReturn($file)
            ->once();

        $fileHelper = $this->getFileHelper(null, $fileSystemMock);
        $fileHelper->moveFirstFile($arrayFiles, $file);
    }

    public function testReadFileLinesIntoArray()
    {
        $file = ASSETS_DIR . 'report.csv';
        $expectedData =  file($file);

        $fileHelper =  $this->getFileHelper();
        $this->assertEquals($expectedData, $fileHelper->readFileLinesIntoArray($file));
    }

    public function testWriteLinesToFile()
    {
        $file = ASSETS_DIR . 'writeTest.txt';
        $data = [
            0 => "first Line\n",
            1 => "second Line\n",
            2 => "third Line\n",
            3 => "fourth Line\n",
        ];

        $fileHelper =  $this->getFileHelper();
        $fileHelper->writeLinesToFile($data, $file);
        $this->assertEquals($data, file($file));

        $this->getFileSystem()->remove($file);
    }

    /**
     * @param Client|null $client
     * @param Filesystem|null $filesystem
     * @param Finder|null $finder
     *
     * @return File
     */
    private function getFileHelper(Client $client = null, Filesystem $filesystem = null, Finder $finder = null)
    {
        $client = ($client == null) ? new Client() : $client;
        $filesystem = ($filesystem == null) ? new Filesystem() : $filesystem;
        $finder = ($finder == null) ? new Finder() : $finder;

        return  new File($client, $filesystem, $finder);
    }

    private function getFileSystem()
    {
        return new Filesystem();
    }
}
