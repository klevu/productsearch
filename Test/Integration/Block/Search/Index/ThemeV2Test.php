<?php

namespace Klevu\Search\Test\Integration\Block\Search\Index;

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController as AbstractControllerTestCase;

class ThemeV2Test extends AbstractControllerTestCase
{
    const KLEVU_LANDING_ELEMENT_REGEX = '#<div +([a-zA-Z-_="\']+ +)*class=(\'|") *((-?[_a-zA-Z]+[_a-zA-Z0-9-]*) +)'
        . '*klevuLanding( +(-?[_a-zA-Z]+[_a-zA-Z0-9-]*))* *(\'|")( +[a-zA-Z-_="\']+)* *></div>#';
    const STYLE_MIN_HEIGHT_REGEX_PREPEND = '#<style type="text&\#x2F;css".*>\s*\.klevuLanding\s*{\s*min-height:\s*';
    const STYLE_MIN_HEIGHT_REGEX_APPEND = "px;\s*}\s*</style>#";

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_search_landing 0
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_search_landing 0
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_quick_search 0
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_quick_search 0
     */
    public function testSRLPContentOutput_ThemeV2()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(static::KLEVU_LANDING_ELEMENT_REGEX, $responseBody);
        } else {
            $this->assertRegExp(static::KLEVU_LANDING_ELEMENT_REGEX, $responseBody);
        }
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Landing Page Js include is present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Landing Page Js include is not present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Landing Page Js include is not present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Landing Page Js include is not present in response body'
            );

            $this->assertStringContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Js include is present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Quick Search Js include is present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Quick Search Js include is Not present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Quick Search Js include is Not present in response body'
            );
        } else {
            $this->assertContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Landing Page Js include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Landing Page Js include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Landing Page Js include is not present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Landing Page Js include is not present in response body'
            );

            $this->assertContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Js include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Js include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Quick Search Js include is Not present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Quick Search Js include is Not present in response body'
            );
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_search_landing 0
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_search_landing 0
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_quick_search 0
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_quick_search 0
     * @magentoConfigFixture default/klevu_frontendjs/configuration/defer_js 1
     * @magentoConfigFixture default_store klevu_frontendjs/configuration/defer_js 1
     */
    public function testSRLPContentOutput_ThemeV2_WithJsDeferred()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(static::KLEVU_LANDING_ELEMENT_REGEX, $responseBody);
        } else {
            $this->assertRegExp(static::KLEVU_LANDING_ELEMENT_REGEX, $responseBody);
        }
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Landing Page Js include is not present in response body'
            );
            $this->assertStringContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Landing Page Js include is present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Landing Page Js include is not present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Landing Page Js include is not present in response body'
            );

            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Js include is not present in response body'
            );
            $this->assertStringContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Js include is present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Quick Search Js include is Not present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Quick Search Js include is Not present in response body'
            );
        } else {
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Landing Page Js include is present in response body'
            );
            $this->assertContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Landing Page Js include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Landing Page Js include is not present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Landing Page Js include is not present in response body'
            );

            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Js include is present in response body'
            );
            $this->assertContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Js include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Quick Search Js include is Not present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Quick Search Js include is Not present in response body'
            );
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_search_landing 1
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_search_landing 1
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_quick_search 1
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_quick_search 1
     */
    public function testSRLPContentOutput_ThemeV2LazyLoadLandingJs()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(static::KLEVU_LANDING_ELEMENT_REGEX, $responseBody);
        } else {
            $this->assertRegExp(static::KLEVU_LANDING_ELEMENT_REGEX, $responseBody);
        }
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Landing Page Js include is not present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Landing Page Js include is not present in response body'
            );
            $this->assertStringContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Landing Page Js include is present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Landing Page Js include is not present in response body'
            );

            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Page Js include is not present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Quick Search Js include is present in response body'
            );
            $this->assertStringContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Quick Search Js include is present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Quick Search Js include is Not present in response body'
            );
        } else {
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Landing Page Js include is not present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Landing Page Js include is present in response body'
            );
            $this->assertContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Landing Page Js include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Landing Page Js include is not present in response body'
            );

            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Page Js include is not present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Js include is present in response body'
            );
            $this->assertContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Quick Search Js include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Quick Search Js include is Not present in response body'
            );
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_search_landing 1
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_search_landing 1
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_quick_search 1
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_quick_search 1
     * @magentoConfigFixture default/klevu_frontendjs/configuration/defer_js 1
     * @magentoConfigFixture default_store klevu_frontendjs/configuration/defer_js 1
     */
    public function testSRLPContentOutput_ThemeV2LazyLoadLandingJs_WithJsDeferred()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(static::KLEVU_LANDING_ELEMENT_REGEX, $responseBody);
        } else {
            $this->assertRegExp(static::KLEVU_LANDING_ELEMENT_REGEX, $responseBody);
        }
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Landing Page Js include is not present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Landing Page Js include is not present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Landing Page Js include is not present in response body'
            );
            $this->assertStringContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Landing Page Js include is present in response body'
            );

            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Page Js include is not present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Quick Search Js include is present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Quick Search Js include is not present in response body'
            );
            $this->assertStringContainsString(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Quick Search Js include is present in response body'
            );
        } else {
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Landing Page Js include is not present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Landing Page Js include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Landing Page Js include is not present in response body'
            );
            $this->assertContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;landing-page-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Landing Page Js include is present in response body'
            );

            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Page Js include is not present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Quick Search Js include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Lazy Load Quick Search Js include is not present in response body'
            );
            $this->assertContains(
                '<script type="text&#x2F;javascript" src="https&#x3A;&#x2F;&#x2F;js.klevu.com&#x2F;theme&#x2F;default&#x2F;v2&#x2F;quick-search-theme.lazyload.js" defer="defer"></script>', // phpcs:ignore Generic.Files.LineLength.TooLong
                $responseBody,
                'Deferred Lazy Load Quick Search Js include is present in response body'
            );
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_search_landing 0
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_search_landing 0
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_quick_search 0
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_quick_search 0
     * @magentoConfigFixture default_store klevu_search/developer/content_min_height_srlp
     */
    public function testSRLPContentOutput_MinHeight_Empty()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        $regex = static::STYLE_MIN_HEIGHT_REGEX_PREPEND
            . '.*'
            . static::STYLE_MIN_HEIGHT_REGEX_APPEND;
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression($regex, $responseBody);
        } else {
            $this->assertNotRegExp($regex, $responseBody);
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_search_landing 0
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_search_landing 0
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_quick_search 0
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_quick_search 0
     * @magentoConfigFixture default_store klevu_search/developer/content_min_height_srlp -768
     */
    public function testSRLPContentOutput_MinHeight_Negative()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        $regex = static::STYLE_MIN_HEIGHT_REGEX_PREPEND
            . '.*'
            . static::STYLE_MIN_HEIGHT_REGEX_APPEND;
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression($regex, $responseBody);
        } else {
            $this->assertNotRegExp($regex, $responseBody);
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_search_landing 0
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_search_landing 0
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_quick_search 0
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_quick_search 0
     * @magentoConfigFixture default_store klevu_search/developer/content_min_height_srlp 768
     */
    public function testSRLPContentOutput_MinHeight_Valid()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        $regex = static::STYLE_MIN_HEIGHT_REGEX_PREPEND
            . '768'
            . static::STYLE_MIN_HEIGHT_REGEX_APPEND;
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression($regex, $responseBody);
        } else {
            $this->assertRegExp($regex, $responseBody);
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v1
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v1
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_search_landing 0
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_search_landing 0
     * @magentoConfigFixture default/klevu_search/developer/lazyload_js_quick_search 0
     * @magentoConfigFixture default_store klevu_search/developer/lazyload_js_quick_search 0
     * @magentoConfigFixture default_store klevu_search/developer/content_min_height_srlp 768
     */
    public function testSRLPContentOutput_MinHeight_ThemeV1()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        $regex = static::STYLE_MIN_HEIGHT_REGEX_PREPEND
            . '.*'
            . static::STYLE_MIN_HEIGHT_REGEX_APPEND;
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression($regex, $responseBody);
        } else {
            $this->assertNotRegExp($regex, $responseBody);
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v1
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v1
     */
    public function testSRLPContentNotOutput_ThemeV1()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression(static::KLEVU_LANDING_ELEMENT_REGEX, $responseBody);
        } else {
            $this->assertNotRegExp(static::KLEVU_LANDING_ELEMENT_REGEX, $responseBody);
        }
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }
}
