<?php


namespace Tests\Werkspot\BingAdsApiBundle\Api\Helper;

use Werkspot\BingAdsApiBundle\Api\Helper\Zip;
use Symfony\Component\Filesystem\Filesystem;

class ZipTest extends \PHPUnit_Framework_TestCase
{

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
