<?php


namespace Tests\Werkspot\BingAdsApiBundle\Tests\Api\Report;


use BingAds\Reporting\ReportFormat;
use BingAds\Reporting\ReportLanguage;
use BingAds\Reporting\ReportRequest;
use Werkspot\BingAdsApiBundle\Api\Report\BaseReport;

class BaseReportTest extends \PHPUnit_Framework_TestCase
{
    public function testSetFormat()
    {
        $report = new BaseReport();
        $this->assertNull($report->getRequest()->Format);

        $report->setFormat(ReportFormat::Csv);
        $this->assertEquals(ReportFormat::Csv, $report->getRequest()->Format);
    }

    public function testSetReturnOnlyCompleteData()
    {
        $report = new BaseReport();
        $this->assertNull($report->getRequest()->ReturnOnlyCompleteData);

        $report->setReturnOnlyCompleteData(true);
        $this->assertTrue($report->getRequest()->ReturnOnlyCompleteData);
    }

    public function testSetReportLanguage()
    {
        $report = new BaseReport();
        $this->assertNull($report->getRequest()->Language);

        $report->setReportLanguage(ReportLanguage::English);
        $this->assertEquals(ReportLanguage::English, $report->getRequest()->Language);
    }

}