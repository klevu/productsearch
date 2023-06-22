<?php
// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
// phpcs:disable Generic.Files.LineLength.TooLong

namespace Klevu\Search\Test\Integration\Controller\Adminhtml\System\Config\Edit\SearchConfiguration;

use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;
use Klevu\Search\Service\Account\GetFeatures;
use Klevu\Search\Service\Account\Model\AccountFeatures;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\Config\Storage\Writer as ScopeConfigWriter;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriterInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class RenderPluginTest extends AbstractBackendControllerTestCase
{
    /**
     * @var string
     */
    private $installDir;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var MockObject&LoggerInterface
     */
    private $loggerMock;

    /**
     * @var MockObject&AccountFeaturesInterface
     */
    private $accountFeaturesMock;

    /**
     * @var MockObject&GetFeaturesInterface
     */
    private $getFeaturesMock;

    /**
     * @var MockObject&ScopeConfigWriterInterface
     */
    private $scopeConfigWriterMock;

    /**
     * @var FileIo
     */
    private $fileIo;

    /**
     * @var string
     */
    protected $resource = 'Klevu_Search::config_search';

    /**
     * @var int
     */
    protected $expectedNoAccessResponseCode = 302;

    /**
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_DefaultScope()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();

        $this->scopeConfigWriterMock->expects($this->never())
            ->method('save');

        $request = $this->getRequest();
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('<div id="system_config_tabs"', $responseBody);
        } else {
            $this->assertContains('<div id="system_config_tabs"', $responseBody);
        }

        // Boosting
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<fieldset[^>]+id="klevu_search_attributes"#', $responseBody);
        } else {
            $this->assertRegExp('#<fieldset[^>]+id="klevu_search_attributes"#', $responseBody);
        }

        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_attributes_info_attribute".*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches);
        $boostingRow = current($matches);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Switch to Store View scope to manage', $boostingRow);
        } else {
            $this->assertContains('Switch to Store View scope to manage', $boostingRow);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_attributes_boosting".*?</tr>#s', $responseBody);
            $this->assertDoesNotMatchRegularExpression('#<(input|select).*?id="klevu_search_attributes_boosting"#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_attributes_boosting".*?</tr>#s', $responseBody);
            $this->assertNotRegexp('#<(input|select).*?id="klevu_search_attributes_boosting"#s', $responseBody);
        }

        // Customer Group Pricing
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<fieldset[^>]+id="klevu_search_price_per_customer_group"#', $responseBody);
        } else {
            $this->assertRegExp('#<fieldset[^>]+id="klevu_search_price_per_customer_group"#', $responseBody);
        }

        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_price_per_customer_group_enabled_info".*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches);
        $boostingRow = current($matches);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Switch to Store View scope to manage', $boostingRow);
        } else {
            $this->assertContains('Switch to Store View scope to manage', $boostingRow);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_price_per_customer_group_enabled".*?</tr>#s', $responseBody);
            $this->assertDoesNotMatchRegularExpression('#<(input|select).*?id="klevu_search_price_per_customer_group_enabled"#s', $responseBody);

            // Preserve Layout Settings (Developer Settings)
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_developer_preserve_layout_log_enabled".*?</tr>#s', $responseBody);
            $this->assertDoesNotMatchRegularExpression('#<(input|select).*?id="klevu_search_developer_preserve_layout_log_enabled"#s', $responseBody);
            $this->assertDoesNotMatchRegularExpression('#<button[^>]+id="klevu_search_developer_preserve_layout_download_log_button".*#s', $responseBody);
            $this->assertDoesNotMatchRegularExpression('#<button[^>]+id="klevu_search_developer_preserve_layout_clear_log_button".*#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_price_per_customer_group_enabled".*?</tr>#s', $responseBody);
            $this->assertNotRegexp('#<(input|select).*?id="klevu_search_price_per_customer_group_enabled"#s', $responseBody);

            // Preserve Layout Settings (Developer Settings)
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_developer_preserve_layout_log_enabled".*?</tr>#s', $responseBody);
            $this->assertNotRegExp('#<(input|select).*?id="klevu_search_developer_preserve_layout_log_enabled"#s', $responseBody);
            $this->assertNotRegExp('#<button[^>]+id="klevu_search_developer_preserve_layout_download_log_button".*#s', $responseBody);
            $this->assertNotRegExp('#<button[^>]+id="klevu_search_developer_preserve_layout_clear_log_button".*#s', $responseBody);
        }
    }

    /**
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_WebsiteScope()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->never())
            ->method('save');

        $request = $this->getRequest();
        $request->setParam('website', $defaultStore->getWebsiteId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('<div id="system_config_tabs"', $responseBody);
        } else {
            $this->assertContains('<div id="system_config_tabs"', $responseBody);
        }

        // Boosting
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<fieldset[^>]+id="klevu_search_attributes"#', $responseBody);
        } else {
            $this->assertRegExp('#<fieldset[^>]+id="klevu_search_attributes"#', $responseBody);
        }

        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_attributes_info_attribute".*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches);
        $boostingRow = current($matches);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Switch to Store View scope to manage', $boostingRow);
        } else {
            $this->assertContains('Switch to Store View scope to manage', $boostingRow);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_attributes_boosting".*?</tr>#s', $responseBody);
            $this->assertDoesNotMatchRegularExpression('#<(input|select).*?id="klevu_search_attributes_boosting"#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_attributes_boosting".*?</tr>#s', $responseBody);
            $this->assertNotRegexp('#<(input|select).*?id="klevu_search_attributes_boosting"#s', $responseBody);
        }

        // Customer Group Pricing
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<fieldset[^>]+id="klevu_search_price_per_customer_group"#', $responseBody);
        } else {
            $this->assertRegExp('#<fieldset[^>]+id="klevu_search_price_per_customer_group"#', $responseBody);
        }

        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_price_per_customer_group_enabled_info".*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches);
        $boostingRow = current($matches);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Switch to Store View scope to manage', $boostingRow);
        } else {
            $this->assertContains('Switch to Store View scope to manage', $boostingRow);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_price_per_customer_group_enabled".*?</tr>#s', $responseBody);
            $this->assertDoesNotMatchRegularExpression('#<(input|select).*?id="klevu_search_price_per_customer_group_enabled"#s', $responseBody);

            // Preserve Layout Settings (Developer Settings)
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_developer_preserve_layout_log_enabled".*?</tr>#s', $responseBody);
            $this->assertDoesNotMatchRegularExpression('#<(input|select).*?id="klevu_search_developer_preserve_layout_log_enabled"#s', $responseBody);
            $this->assertDoesNotMatchRegularExpression('#<button[^>]+id="klevu_search_developer_preserve_layout_download_log_button".*#s', $responseBody);
            $this->assertDoesNotMatchRegularExpression('#<button[^>]+id="klevu_search_developer_preserve_layout_clear_log_button".*#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_price_per_customer_group_enabled".*?</tr>#s', $responseBody);
            $this->assertNotRegexp('#<(input|select).*?id="klevu_search_price_per_customer_group_enabled"#s', $responseBody);

            // Preserve Layout Settings (Developer Settings)
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_developer_preserve_layout_log_enabled".*?</tr>#s', $responseBody);
            $this->assertNotRegExp('#<(input|select).*?id="klevu_search_developer_preserve_layout_log_enabled"#s', $responseBody);
            $this->assertNotRegExp('#<button[^>]+id="klevu_search_developer_preserve_layout_download_log_button".*#s', $responseBody);
            $this->assertNotRegExp('#<button[^>]+id="klevu_search_developer_preserve_layout_clear_log_button".*#s', $responseBody);
        }
    }

    /**
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/searchlanding/landenabled 0
     * @magentoConfigFixture default_store klevu_search/searchlanding/landenabled 0
     * @magentoConfigFixture default/klevu_search/searchlanding/klevu_search_relevance 0
     * @magentoConfigFixture default_store klevu_search/searchlanding/klevu_search_relevance 0
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_PreserveLayoutAvailable_SearchSettings()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->never())
            ->method('save');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_PRESERVES_LAYOUT:
                        $return = true;
                        break;

                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        // Ordering and Rendering
        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_searchlanding_landenabled">.*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Search landing row');
        $searchLandingRow = current($matches);

        $matches = [];
        preg_match('#<select[^>]+id="klevu_search_searchlanding_landenabled"[^>]*>.*?</select>#s', $searchLandingRow, $matches);
        $this->assertCount(1, $matches, 'Search landing field');
        $searchLandingField = current($matches);

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('disabled', $searchLandingField);
        } else {
            $this->assertNotContains('disabled', $searchLandingField);
        }

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<option[^>]+value="0"[^>]+selected.*?>\s*Native\s*</option>#s', $searchLandingField);
            $this->assertMatchesRegularExpression('#<option[^>]+value="2".*?>\s*Klevu JS Theme \(Recommended\)\s*</option>#s', $searchLandingField);
            $this->assertMatchesRegularExpression('#<option[^>]+value="1".*?>\s*Preserve your Magento layout\s*</option>#s', $searchLandingField);
        } else {
            $this->assertRegExp('#<option[^>]+value="0"[^>]+selected.*?>\s*Native\s*</option>#s', $searchLandingField);
            $this->assertRegExp('#<option[^>]+value="2".*?>\s*Klevu JS Theme \(Recommended\)\s*</option>#s', $searchLandingField);
            $this->assertRegExp('#<option[^>]+value="1".*?>\s*Preserve your Magento layout\s*</option>#s', $searchLandingField);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<option[^>]+value="2".*?>\s*Klevu JS Theme\s*</option>#s', $searchLandingField);
        } else {
            $this->assertNotRegExp('#<option[^>]+value="2".*?>\s*Klevu JS Theme\s*</option>#s', $searchLandingField);
        }

        $matches = [];
        preg_match('#<p[^>]+class="note"[^>]*>.*?</p>#s', $searchLandingRow, $matches);
        $this->assertCount(1, $matches, 'Search landing comment');
        $searchLandingComment = current($matches);

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('<strong>Native:</strong>', $searchLandingComment);
            $this->assertStringContainsString('<strong>Klevu JS Theme:</strong>', $searchLandingComment);
            $this->assertStringContainsString('<strong>Preserve your Magento layout:</strong>', $searchLandingComment);
        } else {
            $this->assertContains('<strong>Native:</strong>', $searchLandingComment);
            $this->assertContains('<strong>Klevu JS Theme:</strong>', $searchLandingComment);
            $this->assertContains('<strong>Preserve your Magento layout:</strong>', $searchLandingComment);
        }

        // Sort By Klevu Relevance
        preg_match('#<select[^>]+id="klevu_search_searchlanding_klevu_search_relevance".*?>.*?</select>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Sort by Relevance Field');
        $sortByRelevanceField = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('disabled', $sortByRelevanceField);
        } else {
            $this->assertNotContains('disabled', $sortByRelevanceField);
        }
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<option[^>]+value="1".*?>\s*Yes\s*</option>#s', $sortByRelevanceField);
            $this->assertMatchesRegularExpression('#<option[^>]+value="0"[^>]+selected.*?>\s*No\s*</option>#s', $sortByRelevanceField);
        } else {
            $this->assertRegExp('#<option[^>]+value="1".*?>\s*Yes\s*</option>#s', $sortByRelevanceField);
            $this->assertRegExp('#<option[^>]+value="0"[^>]+selected.*?>\s*No\s*</option>#s', $sortByRelevanceField);
        }

        // Sort Relevance Label
        $matches = [];
        preg_match('#<input[^>]+id="klevu_search_searchlanding_relevance_label".*?/>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Sort Relevance Label Field');
        $sortRelevanceLabelField = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('disabled', $sortRelevanceLabelField);
        } else {
            $this->assertNotContains('disabled', $sortRelevanceLabelField);
        }
    }

    /**
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/searchlanding/landenabled 1
     * @magentoConfigFixture default_store klevu_search/searchlanding/landenabled 1
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_PreserveLayoutUnavailable_EnabledInConfig_SearchSettings()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->atLeastOnce())
            ->method('save')
            ->with(
                'klevu_search/searchlanding/landenabled',
                0,
                'stores',
                (int)$defaultStore->getId()
            );
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('debug')
            ->with('Automatically updated config value for "klevu_search/searchlanding/landenabled" following feature check');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_PRESERVES_LAYOUT:
                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        // Ordering and Rendering
        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_searchlanding_landenabled">.*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Search landing row');
        $searchLandingRow = current($matches);

        $matches = [];
        preg_match('#<select[^>]+id="klevu_search_searchlanding_landenabled"[^>]*>.*?</select>#s', $searchLandingRow, $matches);
        $this->assertCount(1, $matches, 'Search landing field');
        $searchLandingField = current($matches);

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('disabled', $searchLandingField);
        } else {
            $this->assertNotContains('disabled', $searchLandingField);
        }

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<option[^>]+value="0"[^>]+selected.*?>\s*Native\s*</option>#s', $searchLandingField);
            $this->assertMatchesRegularExpression('#<option[^>]+value="2".*?>\s*Klevu JS Theme\s*</option>#s', $searchLandingField);
        } else {
            $this->assertRegExp('#<option[^>]+value="0"[^>]+selected.*?>\s*Native\s*</option>#s', $searchLandingField);
            $this->assertRegExp('#<option[^>]+value="2".*?>\s*Klevu JS Theme\s*</option>#s', $searchLandingField);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<option[^>]+value="2".*?>\s*Klevu JS Theme \(Recommended\)\s*</option>#s', $searchLandingField);
            $this->assertDoesNotMatchRegularExpression('#<option[^>]+value="1".*?>\s*Preserve your Magento layout\s*</option>#s', $searchLandingField);
        } else {
            $this->assertNotRegExp('#<option[^>]+value="2".*?>\s*Klevu JS Theme \(Recommended\)\s*</option>#s', $searchLandingField);
            $this->assertNotRegExp('#<option[^>]+value="1".*?>\s*Preserve your Magento layout\s*</option>#s', $searchLandingField);
        }

        $matches = [];
        preg_match('#<p[^>]+class="note"[^>]*>.*?</p>#s', $searchLandingRow, $matches);
        $this->assertCount(1, $matches, 'Search landing comment');
        $searchLandingComment = current($matches);

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('<strong>Native:</strong>', $searchLandingComment);
            $this->assertStringContainsString('<strong>Klevu JS Theme:</strong>', $searchLandingComment);
        } else {
            $this->assertContains('<strong>Native:</strong>', $searchLandingComment);
            $this->assertContains('<strong>Klevu JS Theme:</strong>', $searchLandingComment);
        }
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('<strong>Preserve your Magento layout:</strong>', $searchLandingComment);
        } else {
            $this->assertNotContains('<strong>Preserve your Magento layout:</strong>', $searchLandingComment);
        }

        // Sort By Klevu Relevance
        preg_match('#<select id="klevu_search_searchlanding_klevu_search_relevance".*?>.*?</select>#s', $responseBody, $matches);
        $this->assertCount(0, $matches, 'Sort by Relevance Field');

        // Sort Relevance Label
        $matches = [];
        preg_match('#<input[^>]+id="klevu_search_searchlanding_relevance_label".*?/>#s', $responseBody, $matches);
        $this->assertCount(0, $matches, 'Sort Relevance Label Field');
    }

    /**
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/searchlanding/landenabled 2
     * @magentoConfigFixture default_store klevu_search/searchlanding/landenabled 2
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_PreserveLayoutUnavailable_ThemeEnabledInConfig_SearchSettings()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->never())
            ->method('save');
        $this->loggerMock->expects($this->never())
            ->method('debug');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_PRESERVES_LAYOUT:
                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        // Ordering and Rendering
        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_searchlanding_landenabled">.*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Search landing row');
        $searchLandingRow = current($matches);

        $matches = [];
        preg_match('#<select[^>]+id="klevu_search_searchlanding_landenabled"[^>]*>.*?</select>#s', $searchLandingRow, $matches);
        $this->assertCount(1, $matches, 'Search landing field');
        $searchLandingField = current($matches);

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('disabled', $searchLandingField);
        } else {
            $this->assertNotContains('disabled', $searchLandingField);
        }

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<option[^>]+value="0".*?>\s*Native\s*</option>#s', $searchLandingField);
            $this->assertMatchesRegularExpression('#<option[^>]+value="2"[^>]+selected.*?>\s*Klevu JS Theme\s*</option>#s', $searchLandingField);
        } else {
            $this->assertRegExp('#<option[^>]+value="0".*?>\s*Native\s*</option>#s', $searchLandingField);
            $this->assertRegExp('#<option[^>]+value="2"[^>]+selected.*?>\s*Klevu JS Theme\s*</option>#s', $searchLandingField);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<option[^>]+value="2".*?>\s*Klevu JS Theme \(Recommended\)\s*</option>#s', $searchLandingField);
            $this->assertDoesNotMatchRegularExpression('#<option[^>]+value="1".*?>\s*Preserve your Magento layout\s*</option>#s', $searchLandingField);
        } else {
            $this->assertNotRegExp('#<option[^>]+value="2".*?>\s*Klevu JS Theme \(Recommended\)\s*</option>#s', $searchLandingField);
            $this->assertNotRegExp('#<option[^>]+value="1".*?>\s*Preserve your Magento layout\s*</option>#s', $searchLandingField);
        }

        $matches = [];
        preg_match('#<p[^>]+class="note"[^>]*>.*?</p>#s', $searchLandingRow, $matches);
        $this->assertCount(1, $matches, 'Search landing comment');
        $searchLandingComment = current($matches);

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('<strong>Native:</strong>', $searchLandingComment);
            $this->assertStringContainsString('<strong>Klevu JS Theme:</strong>', $searchLandingComment);
        } else {
            $this->assertContains('<strong>Native:</strong>', $searchLandingComment);
            $this->assertContains('<strong>Klevu JS Theme:</strong>', $searchLandingComment);
        }
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('<strong>Preserve your Magento layout:</strong>', $searchLandingComment);
        } else {
            $this->assertNotContains('<strong>Preserve your Magento layout:</strong>', $searchLandingComment);
        }

        // Sort By Klevu Relevance
        preg_match('#<select id="klevu_search_searchlanding_klevu_search_relevance".*?>.*?</select>#s', $responseBody, $matches);
        $this->assertCount(0, $matches, 'Sort by Relevance Field');

        // Sort Relevance Label
        $matches = [];
        preg_match('#<input[^>]+id="klevu_search_searchlanding_relevance_label".*?/>#s', $responseBody, $matches);
        $this->assertCount(0, $matches, 'Sort Relevance Label Field');
    }

    /**
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/developer/preserve_layout_log_enabled 1
     * @magentoConfigFixture default_store klevu_search/developer/preserve_layout_log_enabled 1
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_PreserveLayoutAvailable_DeveloperSettings()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->never())
            ->method('save');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_PRESERVES_LAYOUT:
                        $return = true;
                        break;

                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<tr[^>]+id="row_klevu_search_developer_preserve_layout_log_enabled".*?</tr>#s', $responseBody);
            $this->assertMatchesRegularExpression('#<(input|select).*?id="klevu_search_developer_preserve_layout_log_enabled"#s', $responseBody);
        } else {
            $this->assertRegExp('#<(input|select).*?id="klevu_search_developer_preserve_layout_log_enabled"#s', $responseBody);
            $this->assertRegExp('#<tr[^>]+id="row_klevu_search_developer_preserve_layout_log_enabled".*?</tr>#s', $responseBody);
        }
    }

    /**
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/developer/preserve_layout_log_enabled 1
     * @magentoConfigFixture default_store klevu_search/developer/preserve_layout_log_enabled 1
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_PreserveLayoutUnavailable_LoggingEnabledInConfig_DeveloperSettings()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->atLeastOnce())
            ->method('save')
            ->with(
                'klevu_search/developer/preserve_layout_log_enabled',
                0,
                'stores',
                (int)$defaultStore->getId()
            );
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('debug')
            ->with('Automatically updated config value for "klevu_search/developer/preserve_layout_log_enabled" following feature check');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_PRESERVES_LAYOUT:
                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_developer_preserve_layout_log_enabled".*?</tr>#s', $responseBody);
            $this->assertDoesNotMatchRegularExpression('#<(input|select).*?id="klevu_search_developer_preserve_layout_log_enabled"#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_developer_preserve_layout_log_enabled".*?</tr>#s', $responseBody);
            $this->assertNotRegExp('#<(input|select).*?id="klevu_search_developer_preserve_layout_log_enabled"#s', $responseBody);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_BoostingAvailable_DefaultConfig()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->never())
            ->method('save');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_BOOSTING:
                        $return = true;
                        break;

                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<fieldset[^>]+id="klevu_search_attributes"#', $responseBody);
        } else {
            $this->assertRegExp('#<fieldset[^>]+id="klevu_search_attributes"#', $responseBody);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_attributes_info_attribute".*?</tr>#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_attributes_info_attribute".*?</tr>#s', $responseBody);
        }

        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_attributes_boosting.*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Boosting row');
        $boostingRow = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('Switch to Store View scope to manage', $boostingRow);
            $this->assertStringNotContainsString('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $boostingRow);
        } else {
            $this->assertNotContains('Switch to Store View scope to manage', $boostingRow);
            $this->assertNotContains('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $boostingRow);
        }

        $matches = [];
        preg_match('#<select id="klevu_search_attributes_boosting.*?>.*?</select>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Boosting field');
        $boostingField = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('disabled', $boostingField);
        } else {
            $this->assertNotContains('disabled', $boostingField);
        }
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<option[^>]+value=""[^>]+selected.*?>[^<]*</option>#s', $boostingField);
        } else {
            $this->assertRegExp('#<option[^>]+value=""[^>]+selected.*?>[^<]*</option>#s', $boostingField);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_BoostingUnavailable_DefaultConfig()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->never())
            ->method('save');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_BOOSTING:
                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<fieldset[^>]+id="klevu_search_attributes"#', $responseBody);
        } else {
            $this->assertRegExp('#<fieldset[^>]+id="klevu_search_attributes"#', $responseBody);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_attributes_info_attribute".*?</tr>#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_attributes_info_attribute".*?</tr>#s', $responseBody);
        }

        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_attributes_boosting.*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Boosting row');
        $boostingRow = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('Switch to Store View scope to manage', $boostingRow);
        } else {
            $this->assertNotContains('Switch to Store View scope to manage', $boostingRow);
        }
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $boostingRow);
        } else {
            $this->assertContains('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $boostingRow);
        }

        $matches = [];
        preg_match('#<select id="klevu_search_attributes_boosting.*?>.*?</select>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Boosting field');
        $boostingField = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringContainsString('disabled', $boostingField);
        } else {
            $this->assertContains('disabled', $boostingField);
        }
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<option[^>]+value="description".*?>\s*description - Description\s*</option>#s', $boostingField);
            $this->assertMatchesRegularExpression('#<option[^>]+value=""[^>]+selected.*?>[^<]*</option>#s', $boostingField);
        } else {
            $this->assertRegExp('#<option[^>]+value="description".*?>\s*description - Description\s*</option>#s', $boostingField);
            $this->assertRegExp('#<option[^>]+value=""[^>]+selected.*?>[^<]*</option>#s', $boostingField);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/attributes/boosting description
     * @magentoConfigFixture default_store klevu_search/attributes/boosting description
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_BoostingAvailable_EnabledInConfig()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->never())
            ->method('save');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_BOOSTING:
                        $return = true;
                        break;

                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<fieldset[^>]+id="klevu_search_attributes"#', $responseBody);
        } else {
            $this->assertRegExp('#<fieldset[^>]+id="klevu_search_attributes"#', $responseBody);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_attributes_info_attribute".*?</tr>#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_attributes_info_attribute".*?</tr>#s', $responseBody);
        }

        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_attributes_boosting.*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Boosting row');
        $boostingRow = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('Switch to Store View scope to manage', $boostingRow);
            $this->assertStringNotContainsString('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $boostingRow);
        } else {
            $this->assertNotContains('Switch to Store View scope to manage', $boostingRow);
            $this->assertNotContains('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $boostingRow);
        }

        $matches = [];
        preg_match('#<select id="klevu_search_attributes_boosting.*?>.*?</select>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Boosting field');
        $boostingField = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('disabled', $boostingField);
        } else {
            $this->assertNotContains('disabled', $boostingField);
        }
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<option[^>]+value="description"[^>]+selected.*?>\s*description - Description\s*</option>#s', $boostingField);
            $this->assertMatchesRegularExpression('#<option[^>]+value="".*?>[^<]*</option>#s', $boostingField);
        } else {
            $this->assertRegExp('#<option[^>]+value="description"[^>]+selected.*?>\s*description - Description\s*</option>#s', $boostingField);
            $this->assertRegExp('#<option[^>]+value="".*?>[^<]*</option>#s', $boostingField);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/attributes/boosting description
     * @magentoConfigFixture default_store klevu_search/attributes/boosting description
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_BoostingUnavailable_EnabledInConfig()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->atLeastOnce())
            ->method('save')
            ->with(
                'klevu_search/attributes/boosting',
                "",
                'stores',
                (int)$defaultStore->getId()
            );
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('debug')
            ->with('Automatically updated config value for "klevu_search/attributes/boosting" following feature check');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_CMS_FRONT:
                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<fieldset[^>]+id="klevu_search_attributes"#', $responseBody);
        } else {
            $this->assertRegExp('#<fieldset[^>]+id="klevu_search_attributes"#', $responseBody);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_attributes_info_attribute".*?</tr>#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_attributes_info_attribute".*?</tr>#s', $responseBody);
        }

        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_attributes_boosting.*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Boosting row');
        $boostingRow = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('Switch to Store View scope to manage', $boostingRow);
        } else {
            $this->assertNotContains('Switch to Store View scope to manage', $boostingRow);
        }
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $boostingRow);
        } else {
            $this->assertContains('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $boostingRow);
        }

        $matches = [];
        preg_match('#<select id="klevu_search_attributes_boosting.*?>.*?</select>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Boosting field');
        $boostingField = current($matches);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('disabled', $boostingField);
        } else {
            $this->assertContains('disabled', $boostingField);
        }
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<option[^>]+value="description".*?>\s*description - Description\s*</option>#s', $boostingField);
            $this->assertMatchesRegularExpression('#<option[^>]+value=""[^>]+selected.*?>[^<]*</option>#s', $boostingField);
        } else {
            $this->assertRegExp('#<option[^>]+value="description".*?>\s*description - Description\s*</option>#s', $boostingField);
            $this->assertRegExp('#<option[^>]+value=""[^>]+selected.*?>[^<]*</option>#s', $boostingField);
        }
    }

    /**
     * Note: CGP intentionally excluded from features checks. Tests remain to ensure this functionality
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_CustomerGroupPricingAvailable_DefaultConfig()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->never())
            ->method('save');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_ALLOW_GROUP_PRICES:
                        $return = true;
                        break;

                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<fieldset[^>]+id="klevu_search_price_per_customer_group"#', $responseBody);
        } else {
            $this->assertRegExp('#<fieldset[^>]+id="klevu_search_price_per_customer_group"#', $responseBody);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_price_per_customer_group_info".*?</tr>#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_price_per_customer_group_info".*?</tr>#s', $responseBody);
        }

        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_price_per_customer_group.*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Customer Group Pricing row');
        $customerGroupPricingRow = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('Switch to Store View scope to manage', $customerGroupPricingRow);
            $this->assertStringNotContainsString('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $customerGroupPricingRow);
        } else {
            $this->assertNotContains('Switch to Store View scope to manage', $customerGroupPricingRow);
            $this->assertNotContains('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $customerGroupPricingRow);
        }

        $matches = [];
        preg_match('#<select id="klevu_search_price_per_customer_group_enabled.*?>.*?</select>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Customer Group Pricing field');
        $customerGroupPricingField = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('disabled', $customerGroupPricingField);
        } else {
            $this->assertNotContains('disabled', $customerGroupPricingField);
        }
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<option[^>]+value="1".*?>\s*Yes\s*</option>#s', $customerGroupPricingField);
            $this->assertMatchesRegularExpression('#<option[^>]+value="0"[^>]+selected.*?>\s*No\s*</option>#s', $customerGroupPricingField);
        } else {
            $this->assertRegExp('#<option[^>]+value="1".*?>\s*Yes\s*</option>#s', $customerGroupPricingField);
            $this->assertRegExp('#<option[^>]+value="0"[^>]+selected.*?>\s*No\s*</option>#s', $customerGroupPricingField);
        }
    }

    /**
     * Note: CGP intentionally excluded from features checks. Tests remain to ensure this functionality
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_CustomerGroupPricingUnavailable_DefaultConfig()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->never())
            ->method('save');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_ALLOW_GROUP_PRICES:
                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<fieldset[^>]+id="klevu_search_price_per_customer_group"#', $responseBody);
        } else {
            $this->assertRegExp('#<fieldset[^>]+id="klevu_search_price_per_customer_group"#', $responseBody);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_price_per_customer_group_info".*?</tr>#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_price_per_customer_group_info".*?</tr>#s', $responseBody);
        }

        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_price_per_customer_group.*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Customer Group Pricing row');
        $customerGroupPricingRow = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('Switch to Store View scope to manage', $customerGroupPricingRow);
            $this->assertStringNotContainsString('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $customerGroupPricingRow);
        } else {
            $this->assertNotContains('Switch to Store View scope to manage', $customerGroupPricingRow);
            $this->assertNotContains('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $customerGroupPricingRow);
        }

        $matches = [];
        preg_match('#<select id="klevu_search_price_per_customer_group_enabled.*?>.*?</select>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Customer Group Pricing field');
        $customerGroupPricingField = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('disabled', $customerGroupPricingField);
        } else {
            $this->assertNotContains('disabled', $customerGroupPricingField);
        }
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<option[^>]+value="1".*?>\s*Yes\s*</option>#s', $customerGroupPricingField);
            $this->assertMatchesRegularExpression('#<option[^>]+value="0"[^>]+selected.*?>\s*No\s*</option>#s', $customerGroupPricingField);
        } else {
            $this->assertRegExp('#<option[^>]+value="0"[^>]+selected.*?>\s*No\s*</option>#s', $customerGroupPricingField);
            $this->assertRegExp('#<option[^>]+value="1".*?>\s*Yes\s*</option>#s', $customerGroupPricingField);
        }
    }

    /**
     * Note: CGP intentionally excluded from features checks. Tests remain to ensure this functionality
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/price_per_customer_group/enabled 1
     * @magentoConfigFixture default_store klevu_search/price_per_customer_group/enabled 1
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_CustomerGroupPricingAvailable_EnabledInConfig()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->never())
            ->method('save');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_ALLOW_GROUP_PRICES:
                        $return = true;
                        break;

                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<fieldset[^>]+id="klevu_search_price_per_customer_group"#', $responseBody);
        } else {
            $this->assertRegExp('#<fieldset[^>]+id="klevu_search_price_per_customer_group"#', $responseBody);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_price_per_customer_group_enabled_info".*?</tr>#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_price_per_customer_group_enabled_info".*?</tr>#s', $responseBody);
        }

        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_price_per_customer_group_enabled.*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Customer Group Pricing row');
        $customerGroupPricingRow = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('Switch to Store View scope to manage', $customerGroupPricingRow);
            $this->assertStringNotContainsString('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $customerGroupPricingRow);
        } else {
            $this->assertNotContains('Switch to Store View scope to manage', $customerGroupPricingRow);
            $this->assertNotContains('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $customerGroupPricingRow);
        }

        $matches = [];
        preg_match('#<select id="klevu_search_price_per_customer_group_enabled.*?>.*?</select>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Customer Group Pricing field');
        $customerGroupPricingField = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('disabled', $customerGroupPricingField);
        } else {
            $this->assertNotContains('disabled', $customerGroupPricingField);
        }
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<option[^>]+value="1"[^>]+selected.*?>\s*Yes\s*</option>#s', $customerGroupPricingField);
            $this->assertMatchesRegularExpression('#<option[^>]+value="0".*?>\s*No\s*</option>#s', $customerGroupPricingField);
        } else {
            $this->assertRegExp('#<option[^>]+value="1"[^>]+selected.*?>\s*Yes\s*</option>#s', $customerGroupPricingField);
            $this->assertRegExp('#<option[^>]+value="0".*?>\s*No\s*</option>#s', $customerGroupPricingField);
        }
    }

    /**
     * Note: CGP intentionally excluded from features checks. Tests remain to ensure this functionality
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoConfigFixture default/klevu_search/price_per_customer_group/enabled 1
     * @magentoConfigFixture default_store klevu_search/price_per_customer_group/enabled 1
     * @magentoConfigFixture default/klevu_search/add_to_cart/enabledaddtocartfront 0
     * @magentoConfigFixture default_store klevu_search/add_to_cart/enabledaddtocartfront 0
     */
    public function testRender_StoreScope_CustomerGroupPricingUnavailable_EnabledInConfig()
    {
        $this->setupPhp5();
        $this->createPreserveLayoutLogFiles();
        $defaultStore = $this->storeManager->getDefaultStoreView();

        $this->scopeConfigWriterMock->expects($this->never())
            ->method('save');
        $this->loggerMock->expects($this->never())
            ->method('debug');

        $this->accountFeaturesMock->method('isFeatureAvailable')->willReturnCallback(
            static function ($feature, $strict = false) {
                switch ($feature) {
                    case AccountFeatures::PM_FEATUREFLAG_ALLOW_GROUP_PRICES:
                    default:
                        $return = false;
                        break;
                }

                return $return;
            }
        );

        $request = $this->getRequest();
        $request->setParam('store', $defaultStore->getId());
        $request->setParam('section', 'klevu_search');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->cleanupPreserveLayoutLogFilesFiles();

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertNotSame(404, $httpResponseCode);
        $this->assertNotSame($this->expectedNoAccessResponseCode, $httpResponseCode);

        $responseBody = $response->getBody();

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<fieldset[^>]+id="klevu_search_price_per_customer_group"#', $responseBody);
        } else {
            $this->assertRegExp('#<fieldset[^>]+id="klevu_search_price_per_customer_group"#', $responseBody);
        }
        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression('#<tr[^>]+id="row_klevu_search_price_per_customer_group_enabled_info".*?</tr>#s', $responseBody);
        } else {
            $this->assertNotRegExp('#<tr[^>]+id="row_klevu_search_price_per_customer_group_enabled_info".*?</tr>#s', $responseBody);
        }

        $matches = [];
        preg_match('#<tr[^>]+id="row_klevu_search_price_per_customer_group_enabled.*?</tr>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Customer Group Pricing row');
        $customerGroupPricingRow = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('Switch to Store View scope to manage', $customerGroupPricingRow);
            $this->assertStringNotContainsString('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $customerGroupPricingRow);
        } else {
            $this->assertNotContains('Switch to Store View scope to manage', $customerGroupPricingRow);
            $this->assertNotContains('<div class="klevu-upgrade-block">TEST UPGRADE MESSAGE</div>', $customerGroupPricingRow);
        }

        $matches = [];
        preg_match('#<select id="klevu_search_price_per_customer_group_enabled.*?>.*?</select>#s', $responseBody, $matches);
        $this->assertCount(1, $matches, 'Customer Group Pricing field');
        $customerGroupPricingField = current($matches);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('disabled', $customerGroupPricingField);
        } else {
            $this->assertNotContains('disabled', $customerGroupPricingField);
        }
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#<option[^>]+value="1"[^>]+selected.*?>\s*Yes\s*</option>#s', $customerGroupPricingField);
            $this->assertMatchesRegularExpression('#<option[^>]+value="0".*?>\s*No\s*</option>#s', $customerGroupPricingField);
        } else {
            $this->assertRegExp('#<option[^>]+value="1"[^>]+selected.*?>\s*Yes\s*</option>#s', $customerGroupPricingField);
            $this->assertRegExp('#<option[^>]+value="0".*?>\s*No\s*</option>#s', $customerGroupPricingField);
        }
    }

    /**
     * @inheritdoc
     */
    public function testAclHasAccess()
    {
        $this->setupPhp5();

        if ($this->uri === null) {
            $this->markTestIncomplete('AclHasAccess test is not complete');
        }
        if ($this->httpMethod) {
            $this->getRequest()->setMethod($this->httpMethod);
        }
        $this->dispatch($this->uri);
        $this->assertNotSame(404, $this->getResponse()->getHttpResponseCode());
        $this->assertNotSame($this->expectedNoAccessResponseCode, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @inheritdoc
     */
    public function testAclNoAccess()
    {
        $this->setupPhp5();
        if ($this->resource === null || $this->uri === null) {
            $this->markTestIncomplete('Acl test is not complete');
        }
        if ($this->httpMethod) {
            $this->getRequest()->setMethod($this->httpMethod);
        }
        $this->_objectManager->get(\Magento\Framework\Acl\Builder::class)
            ->getAcl()
            ->deny($this->_auth->getUser()->getRoles(), $this->resource);
        $this->dispatch($this->uri);
        $this->assertSame($this->expectedNoAccessResponseCode, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * Alternative setup method to accommodate lack of return type casting in PHP5.6,
     *  given setUp() requires a void return type
     *
     * @return void
     * @throws AuthenticationException
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->installDir = $GLOBALS['installDir'];
        $this->setUp();

        $this->storeManager = $this->_objectManager->get(StoreManagerInterface::class);

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->_objectManager->addSharedInstance($this->loggerMock, 'Klevu\Search\Logger\Logger\Search');

        $this->scopeConfigWriterMock = $this->getMockBuilder(ScopeConfigWriter::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();
        $this->_objectManager->addSharedInstance($this->scopeConfigWriterMock, ScopeConfigWriterInterface::class);
        $this->_objectManager->addSharedInstance($this->scopeConfigWriterMock, ScopeConfigWriter::class);

        $this->accountFeaturesMock = $this->getMockBuilder(AccountFeatures::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFeatureAvailable', 'getUpgradeMessage'])
            ->getMock();
        $this->accountFeaturesMock->method('getUpgradeMessage')
            ->willReturn('TEST UPGRADE MESSAGE');

        $this->getFeaturesMock = $this->getMockBuilder(GetFeaturesInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock();
        $this->getFeaturesMock->method('execute')->willReturn($this->accountFeaturesMock);

        $this->_objectManager->addSharedInstance($this->getFeaturesMock, GetFeaturesInterface::class);
        $this->_objectManager->addSharedInstance($this->getFeaturesMock, GetFeatures::class);

        $this->fileIo = $this->_objectManager->get(FileIo::class);

        $this->uri = $this->getAdminFrontName() . '/admin/system_config/edit/section/klevu_search';
    }

    /**
     * @return void
     */
    private function createPreserveLayoutLogFiles()
    {
        if (!$this->fileIo->fileExists($this->installDir . '/var/log/Klevu_Search_Preserve_Layout.default.log')) {
            $this->fileIo->write($this->installDir . '/var/log/Klevu_Search_Preserve_Layout.default.log', '');
        }
        if (!$this->fileIo->fileExists($this->installDir . '/var/log/Klevu_Search_Preserve_Layout.log')) {
            $this->fileIo->write($this->installDir . '/var/log/Klevu_Search_Preserve_Layout.log', '');
        }
    }

    /**
     * @return void
     */
    private function cleanupPreserveLayoutLogFilesFiles()
    {
        if ($this->fileIo->fileExists($this->installDir . '/var/log/Klevu_Search_Preserve_Layout.log')) {
            $this->fileIo->rm($this->installDir . '/var/log/Klevu_Search_Preserve_Layout.log', '');
        }
        if ($this->fileIo->fileExists($this->installDir . '/var/log/Klevu_Search_Preserve_Layout.default.log')) {
            $this->fileIo->rm($this->installDir . '/var/log/Klevu_Search_Preserve_Layout.default.log', '');
        }
    }

    /**
     * Returns configured admin front name for use in dispatching controller requests
     *
     * @return string
     */
    private function getAdminFrontName()
    {
        /** @var AreaList $areaList */
        $areaList = $this->_objectManager->get(AreaList::class);
        $adminFrontName = $areaList->getFrontName('adminhtml');
        if (!$adminFrontName) {
            /** @var FrontNameResolver $backendFrontNameResolver */
            $backendFrontNameResolver = $this->_objectManager->get(FrontNameResolver::class);
            $adminFrontName = $backendFrontNameResolver->getFrontName(true);
        }

        return (string)$adminFrontName;
    }
}
