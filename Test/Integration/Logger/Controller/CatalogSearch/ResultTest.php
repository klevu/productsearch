<?php

namespace Klevu\Search\Test\Integration\Logger\Controller\CatalogSearch;

use Klevu\Search\Model\Api\Request\Get as ApiGetRequest;
use Klevu\Search\Model\Api\Response\Data as ResponseData;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController as AbstractControllerTestCase;

class ResultTest extends AbstractControllerTestCase
{
    /**
     * @var string
     */
    private $installDir;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var IndexerFactory
     */
    private $indexerFactory;

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadStoreFixtures
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture es_es_store klevu_search/general/enabled 1
     * @magentoConfigFixture es_es_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture es_es_store klevu_search/general/rest_api_key abcdef1234567890
     * @magentoConfigFixture es_es_store klevu_search/searchlanding/landenabled 1
     * @magentoConfigFixture es_es_store klevu_search/searchlanding/klevu_search_relevance 1
     * @magentoConfigFixture es_es_store klevu_search/developer/force_log 1
     * @magentoConfigFixture es_es_store klevu_search/developer/log_level 7
     * @magentoConfigFixture default/klevu_search/developer/preserve_layout_log_enabled 1
     * @magentoConfigFixture es_es_store klevu_search/developer/preserve_layout_log_enabled 1
     * @magentoConfigFixture es_es_store klevu_logger/preserve_layout_configuration/min_log_level 7
     */
    public function testPreserveLayoutLogging_EnabledGlobalEnabledStore()
    {
        $this->setupPhp5();

        $logFileName = 'Klevu_Search_Preserve_Layout.es_es.log';
        $logFilePath = $this->installDir . '/var/log/' . $logFileName;

        $this->removeExistingLogFile($logFilePath);
        $this->assertFalse(file_exists($logFilePath), 'Log file ' . $logFileName . ' exists before search results dispatch');

        $this->storeManager->setCurrentStore('es_es');

        $indexes = [
            'catalog_product_attribute',
            'catalog_product_price',
            'cataloginventory_stock',
            'inventory',
            'catalog_category_product',
            'catalog_product_category',
            'catalogsearch_fulltext',
        ];
        foreach ($indexes as $index) {
            $indexer = $this->indexerFactory->create();
            try {
                $indexer->load($index);
                $indexer->reindexAll();
            } catch (\InvalidArgumentException $e) {
                // Support for older versions of Magento which may not have all indexers
                continue;
            }
        }

        /** @var ProductCollection $productCollection */
        $productCollection = $this->objectManager->create(ProductCollection::class);
        $productCollection->addFieldToFilter('visibility', ['in' => [3,4]]);
        $productCollection->addFieldToFilter('status', 1);
        $requestPartialMock = $this->getApiRequestPartialMock(
            $this->getResponseDataObject($productCollection)
        );
        $this->objectManager->addSharedInstance($requestPartialMock, ApiGetRequest::class);

        $this->dispatch('catalogsearch/result/index/?q=simple');

        $this->assertTrue(
            false === stripos($this->getResponse()->getBody(), 'Your search returned no results'),
            'SRLP should return results'
        );

        // Note: known failure in 2.1.x - ref KS-9240
        $this->assertTrue(file_exists($logFilePath), 'Log file ' . $logFileName . ' exists after search results dispatch');
    }

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadStoreFixtures
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture es_es_store klevu_search/general/enabled 1
     * @magentoConfigFixture es_es_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture es_es_store klevu_search/general/rest_api_key abcdef1234567890
     * @magentoConfigFixture es_es_store klevu_search/searchlanding/landenabled 1
     * @magentoConfigFixture es_es_store klevu_search/searchlanding/klevu_search_relevance 1
     * @magentoConfigFixture es_es_store klevu_search/developer/force_log 1
     * @magentoConfigFixture es_es_store klevu_search/developer/log_level 7
     * @magentoConfigFixture default/klevu_search/developer/preserve_layout_log_enabled 1
     * @magentoConfigFixture es_es_store klevu_search/developer/preserve_layout_log_enabled 0
     * @magentoConfigFixture es_es_store klevu_logger/preserve_layout_configuration/min_log_level 7
     */
    public function testPreserveLayoutLogging_EnabledGlobalDisabledStore()
    {
        $this->setupPhp5();

        $logFileName = 'Klevu_Search_Preserve_Layout.es_es.log';
        $logFilePath = $this->installDir . '/var/log/' . $logFileName;

        $this->removeExistingLogFile($logFilePath);
        $this->assertFalse(file_exists($logFilePath), 'Log file ' . $logFileName . ' exists before search results dispatch');

        $this->storeManager->setCurrentStore('es_es');

        $indexes = [
            'catalog_product_attribute',
            'catalog_product_price',
            'cataloginventory_stock',
            'inventory',
            'catalog_category_product',
            'catalog_product_category',
            'catalogsearch_fulltext',
        ];
        foreach ($indexes as $index) {
            $indexer = $this->indexerFactory->create();
            try {
                $indexer->load($index);
                $indexer->reindexAll();
            } catch (\InvalidArgumentException $e) {
                // Support for older versions of Magento which may not have all indexers
                continue;
            }
        }

        /** @var ProductCollection $productCollection */
        $productCollection = $this->objectManager->create(ProductCollection::class);
        $productCollection->addFieldToFilter('visibility', ['in' => [3,4]]);
        $productCollection->addFieldToFilter('status', 1);
        $requestPartialMock = $this->getApiRequestPartialMock(
            $this->getResponseDataObject($productCollection)
        );
        $this->objectManager->addSharedInstance($requestPartialMock, ApiGetRequest::class);

        $this->dispatch('catalogsearch/result/index/?q=simple');

        $this->assertTrue(
            false === stripos($this->getResponse()->getBody(), 'Your search returned no results'),
            'SRLP should return results'
        );

        $this->assertFalse(file_exists($logFilePath), 'Log file ' . $logFileName . ' does not exist after search results dispatch');
    }

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadStoreFixtures
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture es_es_store klevu_search/general/enabled 1
     * @magentoConfigFixture es_es_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture es_es_store klevu_search/general/rest_api_key abcdef1234567890
     * @magentoConfigFixture es_es_store klevu_search/searchlanding/landenabled 1
     * @magentoConfigFixture es_es_store klevu_search/searchlanding/klevu_search_relevance 1
     * @magentoConfigFixture es_es_store klevu_search/developer/force_log 1
     * @magentoConfigFixture es_es_store klevu_search/developer/log_level 7
     * @magentoConfigFixture default/klevu_search/developer/preserve_layout_log_enabled 0
     * @magentoConfigFixture es_es_store klevu_search/developer/preserve_layout_log_enabled 1
     * @magentoConfigFixture es_es_store klevu_logger/preserve_layout_configuration/min_log_level 7
     */
    public function testPreserveLayoutLogging_DisabledGlobalEnabledStore()
    {
        $this->setupPhp5();

        $logFileName = 'Klevu_Search_Preserve_Layout.es_es.log';
        $logFilePath = $this->installDir . '/var/log/' . $logFileName;

        $this->removeExistingLogFile($logFilePath);
        $this->assertFalse(file_exists($logFilePath), 'Log file ' . $logFileName . ' exists before search results dispatch');

        $this->storeManager->setCurrentStore('es_es');

        $indexes = [
            'catalog_product_attribute',
            'catalog_product_price',
            'cataloginventory_stock',
            'inventory',
            'catalog_category_product',
            'catalog_product_category',
            'catalogsearch_fulltext',
        ];
        foreach ($indexes as $index) {
            $indexer = $this->indexerFactory->create();
            try {
                $indexer->load($index);
                $indexer->reindexAll();
            } catch (\InvalidArgumentException $e) {
                // Support for older versions of Magento which may not have all indexers
                continue;
            }
        }

        /** @var ProductCollection $productCollection */
        $productCollection = $this->objectManager->create(ProductCollection::class);
        $productCollection->addFieldToFilter('visibility', ['in' => [3,4]]);
        $productCollection->addFieldToFilter('status', 1);
        $requestPartialMock = $this->getApiRequestPartialMock(
            $this->getResponseDataObject($productCollection)
        );
        $this->objectManager->addSharedInstance($requestPartialMock, ApiGetRequest::class);

        $this->dispatch('catalogsearch/result/index/?q=jacket');

        $this->assertTrue(
            false === stripos($this->getResponse()->getBody(), 'Your search returned no results'),
            'SRLP should return results'
        );

        $this->assertTrue(file_exists($logFilePath), 'Log file ' . $logFileName . ' exists after search results dispatch');
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        if (class_exists('Klevu\ApiRequest\Model\Api\Request')) {
            $this->markTestSkipped('Test covered in ApiRequest module');
        }
        $this->installDir = $GLOBALS['installDir'];
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->indexerFactory = $this->objectManager->get(IndexerFactory::class);
    }

    /**
     * Loads store creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadStoreFixtures()
    {
        include __DIR__ . '/../../../_files/storeFixtures.php';
    }

    /**
     * Rolls back store creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadStoreFixturesRollback()
    {
        include __DIR__ . '/../../../_files/storeFixtures_rollback.php';
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

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../../_files/productFixtures.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../../_files/productFixtures_rollback.php';
    }

    /**
     * @param $filePath
     * @return void
     */
    private function removeExistingLogFile($filePath)
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Returns a partial mock of the Api\Request\Get object
     * Method: send() returns passed response object
     *
     * @param ResponseData $response
     * @return ApiGetRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getApiRequestPartialMock(ResponseData $response)
    {
        if (!method_exists($this, 'createPartialMock')) {
            return $this->getApiRequestPartialMockLegacy($response);
        }

        $requestPartialMock = $this->createPartialMock(ApiGetRequest::class, [
            'send',
        ]);
        $requestPartialMock->method('send')->willReturn($response);

        return $requestPartialMock;
    }

    /**
     * @param ResponseData $response
     * @return ApiGetRequest|\PHPUnit_Framework_MockObject_MockObject
     * @see getApiRequestPartialMock
     */
    private function getApiRequestPartialMockLegacy(ResponseData $response)
    {
        $requestPartialMock = $this->getMockBuilder(ApiGetRequest::class)
            ->setMethods(['send'])
            ->disableOriginalConstructor()
            ->getMock();

        $requestPartialMock->expects($this->any())
            ->method('send')
            ->willReturn($response);

        return $requestPartialMock;
    }

    /**
     * Creates an API response object based on passed product collection
     *
     * @param ProductCollection $productCollection
     * @return ResponseData
     */
    private function getResponseDataObject(ProductCollection $productCollection)
    {
        /** @var ResponseData $responseData */
        $responseData = $this->objectManager->create(ResponseData::class);
        $responseData->setData([
            'meta' => [
                'totalResultsFound' => $productCollection->getSize(),
                'typeOfQuery' => 'WILDCARD_AND',
                'paginationStartFrom' => 0,
                'noOfResults' => '2000',
                'notificatioCode' => '1', // [sic]
                'storeBaseCurrency' => [],
                'excludeIds' => [],
            ],
            'result' => array_map(static function ($productId) {
                return [
                    'id' => $productId,
                    'itemGroupId' => '',
                ];
            }, $productCollection->getColumnValues('entity_id')),
        ]);

        return $responseData;
    }
}
