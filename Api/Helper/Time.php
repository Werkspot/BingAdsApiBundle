<?php
namespace Werkspot\BingAdsApiBundle\Api\Helper;

class Time
{
    /**
     * @param int $seconds
     *
     * @codeCoverageIgnore
     */
    public function sleep($seconds)
    {
        sleep($seconds);
    }
}
