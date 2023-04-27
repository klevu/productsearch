<?php

namespace Klevu\Search\Test\Integration\Controller\Adminhtml\Integration;

use Klevu\Search\Service\Account\KlevuApi\GetAccountDetails;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\Acl\Builder;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriter;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;

abstract class AbstractIntegrationControllerTestCase extends AbstractBackendControllerTestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;
    /**
     * @var string
     */
    protected $uri;
    /**
     * @var mixed|null
     */
    protected $restApiKey;
    /**
     * @var mixed|null
     */
    protected $jsApiKey;
    /**
     * @var mixed|null
     */
    protected $restApiUrl;

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testAclHasAccess()
    {
        $this->setUpPhp5();
        if (!$this->restApiKey || !$this->jsApiKey || !$this->restApiUrl) {
            $this->markTestSkipped('Klevu API keys are not set in `dev/tests/unit/phpunit.xml`. Test Skipped');
        }
        if ($this->uri === null) {
            $this->markTestIncomplete('AclHasAccess test is not complete');
        }
        $this->createRequest();

        $this->dispatch($this->getAdminFrontName() . $this->uri);
        $this->assertNotSame(404, $this->getResponse()->getHttpResponseCode());
        $this->assertNotSame($this->expectedNoAccessResponseCode, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * Test ACL actually denying access.
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testAclNoAccess()
    {
        $this->setUpPhp5();
        if (!$this->restApiKey || !$this->jsApiKey || !$this->restApiUrl) {
            $this->markTestSkipped('Klevu API keys are not set in `dev/tests/unit/phpunit.xml`. Test Skipped');
        }
        if ($this->resource === null || $this->uri === null) {
            $this->markTestIncomplete('Acl test is not complete');
        }
        $this->_objectManager->get(Builder::class)
            ->getAcl()
            ->deny($this->_auth->getUser()->getRoles(), $this->resource);

        $this->createRequest();

        $this->dispatch($this->getAdminFrontName() . $this->uri);
        $this->assertSame($this->expectedNoAccessResponseCode, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    protected function assertNoneLocalizedExceptionWasNotThrown(ResponseInterface $response)
    {
        $notExpected = 'An internal error occurred. Please check logs for details';
        $actual = $response->getReasonPhrase();
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString($notExpected, $actual);
        } else {
            $this->assertNotContains($notExpected, $actual);
        }
    }

    /**
     * Returns configured admin front name for use in dispatching controller requests
     *
     * @return string
     */
    protected function getAdminFrontName()
    {
        /** @var AreaList $areaList */
        $areaList = $this->objectManager->create(AreaList::class);
        $adminFrontName = $areaList->getFrontName('adminhtml');
        if (!$adminFrontName) {
            /** @var FrontNameResolver $backendFrontNameResolver */
            $backendFrontNameResolver = $this->objectManager->create(FrontNameResolver::class);
            $adminFrontName = $backendFrontNameResolver->getFrontName(true);
        }

        return (string)$adminFrontName;
    }

    /**
     * @param int|null $storeId
     * @param string|null $jsApiKey
     * @param string|null $restApiKey
     *
     * @return void
     * @throws NoSuchEntityException
     */
    protected function createRequest($jsApiKey = null, $restApiKey = null, $storeId = null)
    {
        if (!$storeId) {
            $store = $this->getStore();
            $storeId = $store->getId();
        }
        $postData = [
            GetAccountDetails::REQUEST_PARAM_JS_API_KEY => $jsApiKey?: $this->jsApiKey,
            GetAccountDetails::REQUEST_PARAM_REST_API_KEY => $restApiKey?: $this->restApiKey
        ];
        $request = $this->getRequest();
        $request->setPostValue($postData);
        $request->setMethod($this->httpMethod);
        $request->setParams(['store_id' => $storeId]);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    protected function getStore($storeCode = 'klevu_test_store_1')
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @param int|null $storeId
     *
     * @return void
     * @throws NoSuchEntityException
     * @todo remove once support for PHP 5.6 is dropped
     */
    protected function setUpPhp5($storeId = null)
    {
        $this->objectManager = ObjectManager::getInstance();
        if (!$storeId) {
            $store = $this->getStore();
            $storeId = $store->getId();
        }
        /**
         * This test requires your Klevu API keys
         * These API keys can be set in dev/tests/integration/phpunit.xml
         * <phpunit>
         *     <testsuites>
         *      ...
         *     </testsuites>
         *     <php>
         *         ...
         *         <env name="KLEVU_JS_API_KEY" value="" force="true" />
         *         <env name="KLEVU_REST_API_KEY" value="" force="true" />
         *         <env name="KLEVU_REST_API_URL" value="" force="true" />
         *     </php>
         */
        $this->restApiKey = isset($_ENV['KLEVU_REST_API_KEY']) ? $_ENV['KLEVU_REST_API_KEY'] : null;
        $this->jsApiKey = isset($_ENV['KLEVU_JS_API_KEY']) ? $_ENV['KLEVU_JS_API_KEY'] : null;
        $this->restApiUrl = isset($_ENV['KLEVU_REST_API_URL']) ? $_ENV['KLEVU_REST_API_URL'] : null;

        $scopeConfigWriter = $this->objectManager->get(ScopeConfigWriter::class);
        $scopeConfigWriter->save(
            GetAccountDetails::XML_PATH_API_URL,
            $this->restApiUrl,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }
}
