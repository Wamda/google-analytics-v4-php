<?php

namespace wamda\simple_analytics;

use Google_Client; 
use Google_Service_AnalyticsReporting;
use Google_Auth_AssertionCredentials;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_Dimension;
use Google_Service_AnalyticsReporting_ReportRequest;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_DimensionFilter;
use Google_Service_AnalyticsReporting_DimensionFilterClause;
use Google_Service_AnalyticsReporting_OrderBy;


class AnalyticsClass
{
    private $client;
    private $viewId;

    public function __construct($viewId)
    {
        $this->client = $this->initializeClient();
        $this->viewId = $viewId;
    }

    private function initializeClient() {
        $client = new Google_Client();
        $client->setScopes('https://www.googleapis.com/auth/analytics.readonly');
        $client->setApplicationName('Analytics Reporting');

        $analyticsreporting = new Google_Service_AnalyticsReporting($client);

        $client->setAuthConfig('./credentials.json');

        return $analyticsreporting;
    }


    /** 
     * 
     * @param $pageUrl, $dateFrom, $dateTo 
     * @param $dateFrom, $dateTo 
     * @param $dateTo 
     * @return 
     */
    public function getPageMetrics($pageUrl, $dateFrom, $dateTo)
    {
         $dateRange = new Google_Service_AnalyticsReporting_DateRange();
         $dateRange->setStartDate($dateFrom);
         $dateRange->setEndDate($dateTo);

         $metricKeys= [
            'ga:pageviews' => 'pageviews',
            'ga:users' => 'users',
            'ga:avgTimeOnPage' => 'avgTimeOnPage',
            'ga:bounceRate' => 'bounceRate'
         ];

         $metrics = [];

         foreach($metricKeys as $metric => $alias) {
             $reportingMetric = new Google_Service_AnalyticsReporting_Metric();
             $reportingMetric->setExpression($metric);
             $reportingMetric->setAlias($alias);

             array_push($metrics, $reportingMetric);
         }

         $filters = new Google_Service_AnalyticsReporting_DimensionFilter();
         $filters->setDimensionName('ga:pagepath');

         $filters->setExpressions([$pageUrl]);

         $filterClause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
         $filterClause->setFilters([$filters]);

         $res = $this->performRequest([
             'dateRange' => $dateRange,
             'metrics' => $metrics,
             'filterClause' => $filterClause
         ]);

         $headers = $res['modelData']['reports'][0]['columnHeader']['metricHeader']['metricHeaderEntries'];
         $metrics = $res['modelData']['reports'][0]['data']['rows'][0]['metrics'][0]['values'];

         $resultSet = [];

         for($i = 0; $i < count($headers); $i++) {
            $headerName = $headers[$i]['name'];
            $columnType = $headers[$i]['type'];

            if ($columnType == 'INTEGER') {
                $metricValue = intval($metrics[$i]);
            } elseif ($columnType == 'FLOAT' || 
                      $columnType == 'TIME' || 
                      $columnType == 'PERCENT') {
                $metricValue = floatval($metrics[$i]);
            } else {
                $metricValue = $metrics[$i]; 
            }

            $resultSet[$headerName] = $metricValue;
         }

         return $resultSet;
    }


    /** 
     * 
     * @param $pageUrl, $dateFrom, $dateTo 
     * @param $dateFrom, $dateTo 
     * @param $dateTo 
     * @return 
     */
    public function getPageViewsByTrafficSource($pageUrl, $dateFrom, $dateTo) {
         $dateRange = new Google_Service_AnalyticsReporting_DateRange();
         $dateRange->setStartDate($dateFrom);
         $dateRange->setEndDate($dateTo);

         $metricKeys= [
            'ga:pageviews' => 'pageviews'
         ];

         $metrics = [];

         foreach($metricKeys as $metric => $alias) {
             $reportingMetric = new Google_Service_AnalyticsReporting_Metric();
             $reportingMetric->setExpression($metric);
             $reportingMetric->setAlias($alias);

             array_push($metrics, $reportingMetric);
         }

         $filters = new Google_Service_AnalyticsReporting_DimensionFilter();
         $filters->setDimensionName('ga:pagepath');

         $filters->setExpressions([$pageUrl]);

         $filterClause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
         $filterClause->setFilters([$filters]);

         $channelGroupingDimension = new Google_Service_AnalyticsReporting_Dimension();
         $channelGroupingDimension->setName('ga:channelGrouping');

         $res = $this->performRequest([
             'dateRange' => $dateRange,
             'metrics' => $metrics,
             'filterClause' => $filterClause,
             'dimensions' => [$channelGroupingDimension]
         ]);

         $resultSet = [];

         $rows = $res['modelData']['reports'][0]['data']['rows'];

         foreach($rows as $row) {
            $resultSet[$row['dimensions'][0]] = intval($row['metrics'][0]['values'][0]);
         }

         return $resultSet;
    }


    public function getPageViewsByCountry($pageUrl, $dateFrom, $dateTo) {
         $dateRange = new Google_Service_AnalyticsReporting_DateRange();
         $dateRange->setStartDate($dateFrom);
         $dateRange->setEndDate($dateTo);

         $metricKeys= [
            'ga:pageviews' => 'pageviews'
         ];

         $metrics = [];

         foreach($metricKeys as $metric => $alias) {
             $reportingMetric = new Google_Service_AnalyticsReporting_Metric();
             $reportingMetric->setExpression($metric);
             $reportingMetric->setAlias($alias);

             array_push($metrics, $reportingMetric);
         }

         $filters = new Google_Service_AnalyticsReporting_DimensionFilter();
         $filters->setDimensionName('ga:pagepath');

         $filters->setExpressions([$pageUrl]);

         $filterClause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
         $filterClause->setFilters([$filters]);

         $countryDimension = new Google_Service_AnalyticsReporting_Dimension();
         $countryDimension->setName('ga:country');

         $res = $this->performRequest([
             'dateRange' => $dateRange,
             'metrics' => $metrics,
             'filterClause' => $filterClause,
             'dimensions' => [$countryDimension],
             'orderBys' => [
                 'fieldName' => 'ga:pageviews',
                 'sortOrder' => 'DESCENDING'
             ],
             'pageSize' => 10
         ]);

         $rows = $res['reports'][0]['data']['rows'];

         $resultSet = [];

         foreach($rows as $row) {
            $resultSet[$row['dimensions'][0]] = intval($row['metrics'][0]['values'][0]);
         }

         return $resultSet;
    }

    private function performRequest($params) {
         $request = new Google_Service_AnalyticsReporting_ReportRequest();
         $request->setViewId($this->viewId);

         if (isset($params['dateRange'])) {
            $request->setDateRanges($params['dateRange']);
         }

         if (isset($params['metrics'])) {
            $request->setMetrics($params['metrics']);
         }

         if (isset($params['filterClause'])) {
            $request->setDimensionFilterClauses([$params['filterClause']]);
         }

         if (isset($params['orderBys'])) {
            $request->setOrderBys($params['orderBys']);
         }

         if (isset($params['pageSize'])) {
            $request->setPageSize(10);
         }

         if (isset($params['dimensions'])) {
            $request->setDimensions($params['dimensions']);         
         }

         $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
         $body->setReportRequests([$request]);

         return $this->client->reports->batchGet($body);
    }
}
