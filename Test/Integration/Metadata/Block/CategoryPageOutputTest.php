<?php
/** @noinspection PhpSameParameterValueInspection */
/** @noinspection PhpPrivateFieldCanBeLocalVariableInspection */

namespace Klevu\Search\Test\Integration\Metadata\Block;

use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController as AbstractControllerTestCase;

class CategoryPageOutputTest extends AbstractControllerTestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var string
     */
    private $urlSuffix;

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/metadata/enabled 0
     * @magentoConfigFixture default_store klevu_search/metadata/enabled 0
     * @magentoConfigFixture default/klevu_search/recommendations/enabled 0
     * @magentoConfigFixture default_store klevu_search/recommendations/enabled 0
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_frontendjs/configuration/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoDataFixture loadCategoryFixtures
     * @noinspection PhpParamsInspection
     */
    public function testMetadataJavascriptIsOutputToPageWhenSearchEnabled()
    {
        $this->setupPhp5();

        $this->dispatch($this->prepareUrl('klevu-test-category-1'));

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('<script type="text/javascript" id="klevu_page_meta">', $responseBody);
        } else {
            $this->assertContains('<script type="text/javascript" id="klevu_page_meta">', $responseBody);
        }
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#klevu_page_meta\s*=#', $responseBody);
            $this->assertMatchesRegularExpression('#"pageType"\s*:\s*"category"#', $responseBody);
        } else {
            $this->assertRegExp('#klevu_page_meta\s*=#', $responseBody);
            $this->assertRegExp('#"pageType"\s*:\s*"category"#', $responseBody);
        }
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $this->urlSuffix = $this->scopeConfig->getValue(
            CategoryUrlPathGenerator::XML_PATH_CATEGORY_URL_SUFFIX,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Prepare url to dispatch
     *
     * @param string $urlKey
     * @param bool $addSuffix
     * @return string
     */
    private function prepareUrl($urlKey, $addSuffix = true)
    {
        return $addSuffix ? '/' . $urlKey . $this->urlSuffix : '/' . $urlKey;
    }

    /**
     * Loads category creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadCategoryFixtures()
    {
        include __DIR__ . '/../_files/categoryFixtures.php';
    }

    /**
     * Rolls back category creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadCategoryFixturesRollback()
    {
        include __DIR__ . '/../_files/categoryFixtures_rollback.php';
    }
}
