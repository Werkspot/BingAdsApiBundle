<?php


namespace Tests\Werkspot\BingAdsApiBundle\Api\Helper;

use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Werkspot\BingAdsApiBundle\Api\Helper\Zip;
use Symfony\Component\Filesystem\Filesystem;

class ZipTest extends \PHPUnit_Framework_TestCase
{

    public function testDownload()
    {
        $url = 'http://example.com';
        $file = ASSETS_DIR . 'example.txt';
        $clientMock = Mockery::mock(Client::class);
        $clientMock
            ->shouldReceive('request')
            ->with('GET', $url, ['sink' => $file])
            ->once()
            ->andReturn(new Response(200, [], "test"));
        $zipHelper = new Zip($clientMock);
        $result = $zipHelper->download($url, $file);

        $this->assertEquals($file, $result);
    }

    public function testUnZip()
    {
        $fileSystem = new Filesystem();
        $file = ASSETS_DIR . "test.zip";

        $fileSystem->copy(ASSETS_DIR . "report.zip", $file);

        $zipHelper = new Zip();
        $files = $zipHelper->unZip($file);

        $this->assertEquals($files, [ ASSETS_DIR . "0039202.csv"]);

        //-- Clean Files
        foreach ($files as $file) {
            $fileSystem->remove($file);
        }
    }

    public function testCorruptUnZip()
    {
        $this->expectException(\Exception::class);

        $file = ASSETS_DIR . "corrupt.zip";

        $zipHelper = new Zip();
        $zipHelper->unZip($file, false);

    }
}
