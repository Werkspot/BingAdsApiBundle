BingAdsApiBundle
===============
The BingAdsApiBundle provides a simple integration of the [Bing Ads API][bingadsapi] for your Symfony project.

Checkout the Bing Ads full [documentation][bingDocumentation]


**Warning: Currently in development**

[![Build Status](https://travis-ci.org/Werkspot/BingAdsApiBundle.svg?branch=master)](https://travis-ci.org/Werkspot/BingAdsApiBundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Werkspot/BingAdsApiBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Werkspot/BingAdsApiBundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Werkspot/BingAdsApiBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Werkspot/BingAdsApiBundle/?branch=master)

**TODO**
- Create more Reports


Installation
------------
With [composer](http://packagist.org), add:

```json
{
    "require": {
        "werkspot/bing-ads-api-bundle": "dev-master"
    }
}
```

Then enable it in your kernel:

```php
// app/AppKernel.php
public function registerBundles()
{
    $bundles = [
        //...
        new Werkspot\BingAdsApiBundle\WerkspotBingAdsApiBundle(),
        //...
```
Configuration
-------------
```yaml
# app/config/config.yml

# Bing ads API
werkspot_bing_ads_api:
  cache_dir: "%kernel.cache_dir%" #<-- optional
```

Usage
-----

The bundle registers the `werkspot.bing_ads_api_client` service witch allows you to call the api;

### Get Report

```php

use BingAds\Reporting\ReportTimePeriod;

$reportType = 'GeoLocationPerformanceReportRequest';
$timePeriod = ReportTimePeriod::LastMonth;
$columns = [
    'TimePeriod',
    'AccountName',
    'AdGroupId',
    'AdGroupName',
    'Impressions',
    'Clicks',
    'CurrencyCode',
    'Spend',
    'Country',
    'City',
    'State',
    'MetroArea',
    'MostSpecificLocation',
];

$apiDetails = new ApiDetails(
    'refreshToken',
    'clientId',
    'secret',
    'redirectUri',
    'devToken'
);
        
$bingApi = $this->get('werkspot.bing_ads_api_client');
$bingApi->setApiDetails($apiDetails);
$arrayOfFiles = $bingApi->get($columns, $reportType, $timePeriod );

/* [...] Do something with the list */

$bingApi->clearCache(); //-- When done remove the files

$newRefreshToken = $bingApi->getRefreshToken() //-- Get new RefreshToken
```


Credits
-------

BingAdsApiBundle is based on the officical [Bing Ads API][bingadsapi].
BingAdsApiBundle has been developed by [LauLaman][LauLaman].

[bingadsapi]: https://code.msdn.microsoft.com/Bing-Ads-API-Version-9-in-fb27761f
[bingDocumentation]: https://developers.bingads.microsoft.com/
[LauLaman]: https://github.com/LauLaman
