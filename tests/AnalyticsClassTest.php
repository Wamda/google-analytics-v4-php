<?php

namespace wamda\simple_analytics;

class AnalyticsClassTest extends \PHPUnit_Framework_TestCase
{

    public function __construct() {}

    /**
     * Test that true does in fact equal true
     */
    public function testGetBasicMetrics()
    {
        $analytics = new AnalyticsClass('34641751');
        $res = $analytics->getPageMetrics('2016/05/egyptian-social-network-keep-your-privacy', '2016-05-26', '2016-06-01');

        $this->assertArrayHasKey('pageviews', $res);
        $this->assertArrayHasKey('users', $res);
        $this->assertArrayHasKey('avgTimeOnPage', $res);
        $this->assertArrayHasKey('bounceRate', $res);
    }

    public function testGetPageViewsByTrafficSource()
    {
        $analytics = new AnalyticsClass('34641751');
        $res = $analytics->getPageViewsByTrafficSource('2016/05/egyptian-social-network-keep-your-privacy', '2016-05-26', '2016-06-01');

        $this->assertArrayHasKey('Direct', $res);
        $this->assertArrayHasKey('Organic Search', $res);
        $this->assertArrayHasKey('Referral', $res);
        $this->assertArrayHasKey('Social', $res);
    }

    public function testGetPageViewsByCountry() 
    {
        $analytics = new AnalyticsClass('34641751');
        $res = $analytics->getPageViewsByCountry('2016/05/egyptian-social-network-keep-your-privacy', '2016-05-26', '2016-05-26');

        $this->assertNotEmpty($res);
    }
}
