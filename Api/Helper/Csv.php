<?php

namespace Werkspot\BingAdsApiBundle\Api\Helper;

class Csv
{
    /**
     * @param array $lines
     * @param bool $includingColumnHeaders
     * @param int $fileHeaders
     * @param int $columnHeaders
     *
     * @return array
     */
    public function removeHeaders(array $lines, $includingColumnHeaders = true, $fileHeaders = 10, $columnHeaders = 1)
    {
        $lines = array_values($lines);

        $removeLines = ($includingColumnHeaders) ? ($fileHeaders + $columnHeaders) : $fileHeaders;
        for ($i = 0; $i < ($removeLines); ++$i) {
            unset($lines[$i]);
        }

        $lines = array_values($lines);

        return $lines;
    }

    /**
     * @param array $lines
     * @param int   $noOfLinesToRemove
     *
     * @return array
     */
    public function removeLastLines(array $lines, $noOfLinesToRemove = 1)
    {
        $totalLines = count($lines);
        $removeFrom = $totalLines - $noOfLinesToRemove;

        for ($i = $removeFrom; $i < $totalLines; ++$i) {
            unset($lines[$i]);
        }

        $lines = array_values($lines);

        return $lines;
    }

    /**
     * @param array $lines
     * @param string $separator
     * @param string $enclosure
     *
     * @return array
     */
    public function fixDate(array $lines, $separator = ',', $enclosure = '"')
    {
        foreach ($lines as $key => $line) {
            $columns = str_getcsv($line, $separator);
            $isChanged = false;
            foreach ($columns as $columnKey => $column) {
                if (preg_match('/^([1-9]|1[0-2])\/([1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/', $column)) {
                    $date = \DateTime::createFromFormat('m/d/Y', $column);
                    $columns[$columnKey] = $date->format('Y/m/d');
                    $isChanged = true;
                }
            }
            if ($isChanged) {
                $lines[$key] = $this->arrayToCsvLine($columns, $separator, $enclosure);
            }
        }

        return $lines;
    }

    /**
     * @param array $array
     * @param string $separator
     * @param null $enclosure
     *
     * @return string
     */
    public function arrayToCsvLine(array $array, $separator = ',', $enclosure = null)
    {
        $csvStr = '';

        for ($i = 0; $i < count($array); ++$i) {
            if ($enclosure) {
                $csvStr .= $enclosure . str_replace($enclosure, $enclosure . $enclosure, $array[$i]) . $enclosure;
            } else {
                $csvStr .= $array[$i];
            }

            $csvStr .= ($i < count($array) - 1) ? $separator : "\r\n";
        }

        return $csvStr;
    }
}
