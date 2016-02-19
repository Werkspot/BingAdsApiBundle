BingAdsApiBundle
===============
The BingAdsApiBundle provides a simple integration of the [Bing Ads API][bingadsapi] for your Symfony project.

Checkout the Bing Ads full [documentation][bingDocumentation]

**Warning: Currently in development**

**TODO**
- Create more Reports
- Write tests


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
  api_client_id: "%bing_ads_api_client_id%" #<-- Keep them save! (in parameters.yml)
  api_secret: "%bing_ads_api_client_secret%" #<-- Keep them save! (in parameters.yml)
  redirect_uri: "https://example.com/OAuth2Callback.php"
  dev_token: "%bing_ads_api_dev_token%" #<-- Keep them save! (in parameters.yml)
  refresh_token: "%bing_ads_api_refresh_token%" #<-- Keep them save! (in parameters.yml)
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

$bingApi = $this->get('werkspot.bing_ads_api_client');
$arrayOfFiles = $bingApi->get($columns, $reportType, $timePeriod );

/* [...] Do something with the list */

$bingApi->clearCache(); //-- When done remove the files
```


Credits
-------

BingAdsApiBundle is based on the officical [Bing Ads API][bingadsapi].
BingAdsApiBundle has been developed by [LauLaman][LauLaman].

[bingadsapi]: https://code.msdn.microsoft.com/Bing-Ads-API-Version-9-in-fb27761f
[bingDocumentation]: https://developers.bingads.microsoft.com/
[LauLaman]: https://github.com/LauLaman
