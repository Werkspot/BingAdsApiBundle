<?php

namespace Tests\Werkspot\BingAdsApiBundle\Api\Helper;

use Werkspot\BingAdsApiBundle\Api\Helper\Csv;

class CsvTest extends \PHPUnit_Framework_TestCase
{
    public function testRemoveOneLastLine()
    {
        $csvArray = $this->getCsvArray();

        $csvHelper = new Csv();
        $result = $csvHelper->removeLastLines($csvArray);

        $this->assertEquals((count($csvArray) -1), count($result));
    }

    public function testRemoveTwoLastLine()
    {
        $csvArray = $this->getCsvArray();

        $csvHelper = new Csv();
        $result = $csvHelper->removeLastLines($csvArray, 2);

        $this->assertEquals((count($csvArray) -2), count($result));
    }

    public function testFixDate()
    {
        $csvArray = [
            "\"11/11/1988\",\"account_1\",\"1\",\"group 1\",\"1\",\"0\",\"EUR\",\"0.00\",\"Italy\",\"\",\"Bolzano\",\"\",\"Bolzano\"",
            "\"4/22/2007\",\"account_1\",\"2\",\"group 2\",\"4\",\"0\",\"EUR\",\"0.00\",\"Italy\",\"\",\"Milan\",\"\",\"Milan\"",
        ];
        $csvHelper = new Csv();
        $result = $csvHelper->fixDate($csvArray);

        $expectedResult = [
            "\"1988/11/11\",\"account_1\",\"1\",\"group 1\",\"1\",\"0\",\"EUR\",\"0.00\",\"Italy\",\"\",\"Bolzano\",\"\",\"Bolzano\"\r\n",
            "\"2007/04/22\",\"account_1\",\"2\",\"group 2\",\"4\",\"0\",\"EUR\",\"0.00\",\"Italy\",\"\",\"Milan\",\"\",\"Milan\"\r\n",
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testRemoveAllHeaders()
    {
        $csvArray = $this->getCsvArray();
        $csvHelper = new Csv();
        $result = $csvHelper->removeHeaders($csvArray);

        $expectedResult = [
            "\"11/11/1988\",\"account_1\",\"1\",\"group 1\",\"1\",\"0\",\"EUR\",\"0.00\",\"Italy\",\"\",\"Bolzano\",\"\",\"Bolzano\"",
            "\"4/22/2007\",\"account_1\",\"2\",\"group 2\",\"4\",\"0\",\"EUR\",\"0.00\",\"Italy\",\"\",\"Milan\",\"\",\"Milan\"",
            "",
            "\"Ã‚Â©2016 Microsoft Corporation. All rights reserved. \"",
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testRemoveFileHeaders()
    {
        $csvArray = $this->getCsvArray();
        $csvHelper = new Csv();
        $result = $csvHelper->removeHeaders($csvArray, false);

        $expectedResult = [
            "\"GregorianDate\",\"AccountName\",\"AdGroupId\",\"AdGroupName\",\"Impressions\",\"Clicks\",\"CurrencyCode\",\"Spend\",\"CountryOrRegion\",\"City\",\"State\",\"MetroArea\",\"MostSpecificLocation\"",
            "\"11/11/1988\",\"account_1\",\"1\",\"group 1\",\"1\",\"0\",\"EUR\",\"0.00\",\"Italy\",\"\",\"Bolzano\",\"\",\"Bolzano\"",
            "\"4/22/2007\",\"account_1\",\"2\",\"group 2\",\"4\",\"0\",\"EUR\",\"0.00\",\"Italy\",\"\",\"Milan\",\"\",\"Milan\"",
            "",
            "\"Ã‚Â©2016 Microsoft Corporation. All rights reserved. \"",
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testRemoveAllHeadersInCount()
    {
        $csvArray = $this->getCsvArray();
        $csvHelper = new Csv();
        $result = $csvHelper->removeHeaders($csvArray);

        $this->assertEquals((count($csvArray) -(Csv::FILE_HEADERS + Csv::COLUMN_HEADERS)), count($result));
    }

    public function testRemoveFileHeadersAsCount()
    {
        $csvArray = $this->getCsvArray();
        $csvHelper = new Csv();
        $result = $csvHelper->removeHeaders($csvArray, false);

        $this->assertEquals((count($csvArray) -(Csv::FILE_HEADERS)), count($result));
    }


    private function getCsvArray()
    {
        return [
            "\"Report Name: GeoLocationPerformanceReportRequest\"",
            "\"Report Time: 2/28/2016\"",
            "\"Time Zone: (GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna\"",
            "\"Last Completed Available Day: 2/29/2016 9:00:00 AM (GMT)\"",
            "\"Last Completed Available Hour: 2/29/2016 9:00:00 AM (GMT)\"",
            "\"Report Aggregation: Daily\"",
            "\"Report Filter: \"",
            "\"Potential Incomplete Data: false\"",
            "\"Rows: 2\"",
            "",
            "\"GregorianDate\",\"AccountName\",\"AdGroupId\",\"AdGroupName\",\"Impressions\",\"Clicks\",\"CurrencyCode\",\"Spend\",\"CountryOrRegion\",\"City\",\"State\",\"MetroArea\",\"MostSpecificLocation\"",
            "\"11/11/1988\",\"account_1\",\"1\",\"group 1\",\"1\",\"0\",\"EUR\",\"0.00\",\"Italy\",\"\",\"Bolzano\",\"\",\"Bolzano\"",
            "\"4/22/2007\",\"account_1\",\"2\",\"group 2\",\"4\",\"0\",\"EUR\",\"0.00\",\"Italy\",\"\",\"Milan\",\"\",\"Milan\"",
            "",
            "\"Ã‚Â©2016 Microsoft Corporation. All rights reserved. \"",
        ];
    }
}
