<?php

namespace Klevu\Search\Test\Integration\Model\Api\Action;

use Klevu\Search\Model\Api\Action\Startsession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class StartsessionTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @magentoDataFixture loadStoreFixtures
     * @magentoConfigFixture default/klevu_search/general/hostname box.klevu.com
     * @magentoConfigFixture default/klevu_search/general/rest_hostname rest.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/hostname box.ksearchnet.com
     * @magentoConfigFixture default_store klevu_search/general/rest_hostname rest.ksearchnet.com
     * @magentoConfigFixture es_es_store klevu_search/general/hostname box-es.klevu.com
     * @magentoConfigFixture es_es_store klevu_search/general/rest_hostname rest-es.klevu.com
     */
    public function testBuildEndpoint_DefaultStore_GeneratedHostname()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $expectedResult = 'https://box.ksearchnet.com/foo';
        $actualResult = $startsession->buildEndpoint(
            '/foo',
            null,
            null
        );

        $this->assertSame($expectedResult, $actualResult);

        static::loadStoreFixturesRollback();
    }

    /**
     * @magentoDataFixture loadStoreFixtures
     * @magentoConfigFixture default/klevu_search/general/hostname box.klevu.com
     * @magentoConfigFixture default/klevu_search/general/rest_hostname rest.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/hostname box.ksearchnet.com
     * @magentoConfigFixture default_store klevu_search/general/rest_hostname rest.ksearchnet.com
     * @magentoConfigFixture es_es_store klevu_search/general/hostname box-es.klevu.com
     * @magentoConfigFixture es_es_store klevu_search/general/rest_hostname rest-es.klevu.com
     */
    public function testBuildEndpoint_DefaultStore_AdminContext_SpecifiedHostname()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $expectedResult = 'https://box-test.klevu.com/foo';
        $actualResult = $startsession->buildEndpoint(
            '/foo',
            null,
            'box-test.klevu.com'
        );

        $this->assertSame($expectedResult, $actualResult);

        static::loadStoreFixturesRollback();
    }

    /**
     * @magentoDataFixture loadStoreFixtures
     * @magentoConfigFixture default/klevu_search/general/hostname box.klevu.com
     * @magentoConfigFixture default/klevu_search/general/rest_hostname rest.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/hostname box.ksearchnet.com
     * @magentoConfigFixture default_store klevu_search/general/rest_hostname rest.ksearchnet.com
     * @magentoConfigFixture es_es_store klevu_search/general/hostname box-es.klevu.com
     * @magentoConfigFixture es_es_store klevu_search/general/rest_hostname rest-es.klevu.com
     */
    public function testBuildEndpoint_SpecifiedStore_GeneratedHostname()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $esEsStore = $this->storeManager->getStore('es_es');

        $expectedResult = 'https://box-es.klevu.com/foo';
        $actualResult = $startsession->buildEndpoint(
            '/foo',
            $esEsStore,
            null
        );

        $this->assertSame($expectedResult, $actualResult);

        static::loadStoreFixturesRollback();
    }

    /**
     * @magentoDataFixture loadStoreFixtures
     * @magentoConfigFixture default/klevu_search/general/hostname box.klevu.com
     * @magentoConfigFixture default/klevu_search/general/rest_hostname rest.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/hostname box.ksearchnet.com
     * @magentoConfigFixture default_store klevu_search/general/rest_hostname rest.ksearchnet.com
     * @magentoConfigFixture es_es_store klevu_search/general/hostname box-es.klevu.com
     * @magentoConfigFixture es_es_store klevu_search/general/rest_hostname rest-es.klevu.com
     */
    public function testBuildEndpoint_SpecifiedStore_SpecifiedHostname()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $esEsStore = $this->storeManager->getStore('es_es');

        $expectedResult = 'https://box-test.klevu.com/foo';
        $actualResult = $startsession->buildEndpoint(
            '/foo',
            $esEsStore,
            'box-test.klevu.com'
        );

        $this->assertSame($expectedResult, $actualResult);

        static::loadStoreFixturesRollback();
    }

    /**
     * @magentoDataFixture loadStoreFixtures
     * @magentoConfigFixture default/klevu_search/general/hostname box.klevu.com
     * @magentoConfigFixture default/klevu_search/general/rest_hostname rest.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/hostname box.ksearchnet.com
     * @magentoConfigFixture default_store klevu_search/general/rest_hostname rest.ksearchnet.com
     * @magentoConfigFixture es_es_store klevu_search/general/hostname box-es.klevu.com
     * @magentoConfigFixture es_es_store klevu_search/general/rest_hostname rest-es.klevu.com
     */
    public function testGetStore_WithoutData()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $store = $startsession->getStore();

        $this->assertInstanceOf(StoreInterface::class, $store);
        $this->assertSame('default', $store->getCode());

        static::loadStoreFixturesRollback();
    }

    /**
     * @magentoDataFixture loadStoreFixtures
     * @magentoConfigFixture default/klevu_search/general/hostname box.klevu.com
     * @magentoConfigFixture default/klevu_search/general/rest_hostname rest.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/hostname box.ksearchnet.com
     * @magentoConfigFixture default_store klevu_search/general/rest_hostname rest.ksearchnet.com
     * @magentoConfigFixture es_es_store klevu_search/general/hostname box-es.klevu.com
     * @magentoConfigFixture es_es_store klevu_search/general/rest_hostname rest-es.klevu.com
     */
    public function testGetStore_HasData()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $esEsStore = $this->storeManager->getStore('es_es');
        $startsession->setDataUsingMethod('store', $esEsStore);

        $store = $startsession->getStore();

        $this->assertInstanceOf(StoreInterface::class, $store);
        $this->assertSame('es_es', $store->getCode());

        static::loadStoreFixturesRollback();
    }

    /**
     * @magentoDataFixture loadStoreFixtures
     * @magentoConfigFixture default/klevu_search/general/hostname box.klevu.com
     * @magentoConfigFixture default/klevu_search/general/rest_hostname rest.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/hostname box.ksearchnet.com
     * @magentoConfigFixture default_store klevu_search/general/rest_hostname rest.ksearchnet.com
     * @magentoConfigFixture es_es_store klevu_search/general/hostname box-es.klevu.com
     * @magentoConfigFixture es_es_store klevu_search/general/rest_hostname rest-es.klevu.com
     */
    public function testExecute_WithoutStoreParameter_DefaultStore()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $requestMock = $this->getRequestMock();
        $requestMock->expects($this->once())
            ->method('setEndpoint')
            ->with('https://rest.ksearchnet.com/rest/service/startSession')
            ->willReturnSelf();

        $startsession->setRequest($requestMock);

        $startsession->execute([
            'api_key' => 'klevu-1234567890',
        ]);

        static::loadStoreFixturesRollback();
    }

    /**
     * @magentoDataFixture loadStoreFixtures
     * @magentoConfigFixture default/klevu_search/general/hostname box.klevu.com
     * @magentoConfigFixture default/klevu_search/general/rest_hostname rest.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/hostname box.ksearchnet.com
     * @magentoConfigFixture default_store klevu_search/general/rest_hostname rest.ksearchnet.com
     * @magentoConfigFixture es_es_store klevu_search/general/hostname box-es.klevu.com
     * @magentoConfigFixture es_es_store klevu_search/general/rest_hostname rest-es.klevu.com
     */
    public function testExecute_WithoutStoreParameter_SpecifiedStore()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $esEsStore = $this->storeManager->getStore('es_es');
        $startsession->setStore($esEsStore);

        $requestMock = $this->getRequestMock();
        $requestMock->expects($this->once())
            ->method('setEndpoint')
            ->with('https://rest-es.klevu.com/rest/service/startSession')
            ->willReturnSelf();

        $startsession->setRequest($requestMock);

        $startsession->execute([
            'api_key' => 'klevu-1234567890',
        ]);

        static::loadStoreFixturesRollback();
    }

    /**
     * @magentoDataFixture loadStoreFixtures
     * @magentoConfigFixture default/klevu_search/general/hostname box.klevu.com
     * @magentoConfigFixture default/klevu_search/general/rest_hostname rest.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/hostname box.ksearchnet.com
     * @magentoConfigFixture default_store klevu_search/general/rest_hostname rest.ksearchnet.com
     * @magentoConfigFixture es_es_store klevu_search/general/hostname box-es.klevu.com
     * @magentoConfigFixture es_es_store klevu_search/general/rest_hostname rest-es.klevu.com
     */
    public function testExecute_WithStoreParameter_StoreCode()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $requestMock = $this->getRequestMock();
        $requestMock->expects($this->once())
            ->method('setEndpoint')
            ->with('https://rest-es.klevu.com/rest/service/startSession')
            ->willReturnSelf();

        $startsession->setRequest($requestMock);

        $startsession->execute([
            'api_key' => 'klevu-1234567890',
            'store' => 'es_es',
        ]);

        static::loadStoreFixturesRollback();
    }

    /**
     * @magentoDataFixture loadStoreFixtures
     * @magentoConfigFixture default/klevu_search/general/hostname box.klevu.com
     * @magentoConfigFixture default/klevu_search/general/rest_hostname rest.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/hostname box.ksearchnet.com
     * @magentoConfigFixture default_store klevu_search/general/rest_hostname rest.ksearchnet.com
     * @magentoConfigFixture es_es_store klevu_search/general/hostname box-es.klevu.com
     * @magentoConfigFixture es_es_store klevu_search/general/rest_hostname rest-es.klevu.com
     */
    public function testExecute_WithStoreParameter_StoreId()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $esEsStore = $this->storeManager->getStore('es_es');

        $requestMock = $this->getRequestMock();
        $requestMock->expects($this->once())
            ->method('setEndpoint')
            ->with('https://rest-es.klevu.com/rest/service/startSession')
            ->willReturnSelf();

        $startsession->setRequest($requestMock);

        $startsession->execute([
            'api_key' => 'klevu-1234567890',
            'store' => $esEsStore->getId(),
        ]);

        static::loadStoreFixturesRollback();
    }

    /**
     * @magentoDataFixture loadStoreFixtures
     * @magentoConfigFixture default/klevu_search/general/hostname box.klevu.com
     * @magentoConfigFixture default/klevu_search/general/rest_hostname rest.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/hostname box.ksearchnet.com
     * @magentoConfigFixture default_store klevu_search/general/rest_hostname rest.ksearchnet.com
     * @magentoConfigFixture es_es_store klevu_search/general/hostname box-es.klevu.com
     * @magentoConfigFixture es_es_store klevu_search/general/rest_hostname rest-es.klevu.com
     */
    public function testExecute_WithStoreParameter_StoreObject()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $esEsStore = $this->storeManager->getStore('es_es');

        $requestMock = $this->getRequestMock();
        $requestMock->expects($this->once())
            ->method('setEndpoint')
            ->with('https://rest-es.klevu.com/rest/service/startSession')
            ->willReturnSelf();

        $startsession->setRequest($requestMock);

        $startsession->execute([
            'api_key' => 'klevu-1234567890',
            'store' => $esEsStore,
        ]);

        static::loadStoreFixturesRollback();
    }

    /**
     * @magentoDataFixture loadStoreFixtures
     * @magentoConfigFixture default/klevu_search/general/hostname box.klevu.com
     * @magentoConfigFixture default/klevu_search/general/rest_hostname rest.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/hostname box.ksearchnet.com
     * @magentoConfigFixture default_store klevu_search/general/rest_hostname rest.ksearchnet.com
     * @magentoConfigFixture es_es_store klevu_search/general/hostname box-es.klevu.com
     * @magentoConfigFixture es_es_store klevu_search/general/rest_hostname rest-es.klevu.com
     */
    public function testExecute_WithStoreParameter_Null()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $requestMock = $this->getRequestMock();
        $requestMock->expects($this->once())
            ->method('setEndpoint')
            ->with('https://rest.ksearchnet.com/rest/service/startSession')
            ->willReturnSelf();

        $startsession->setRequest($requestMock);

        $startsession->execute([
            'api_key' => 'klevu-1234567890',
            'store' => null,
        ]);

        static::loadStoreFixturesRollback();
    }

    public function testExecute_WithStoreParameter_InvalidType()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $requestMock = $this->getRequestMock();
        $requestMock->expects($this->never())
            ->method('setEndpoint');

        $startsession->setRequest($requestMock);

        $this->expectException(LocalizedException::class);
        $startsession->execute([
            'api_key' => 'klevu-1234567890',
            'store' => (object)[
                'id' => 12345,
                'code' => 'es_es',
            ],
        ]);
    }

    public function testExecute_WithStoreParameter_NotFoundStore()
    {
        $this->setupPhp5();

        /** @var Startsession $startsession */
        $startsession = $this->objectManager->create(Startsession::class);

        $requestMock = $this->getRequestMock();
        $requestMock->expects($this->never())
            ->method('setEndpoint');

        $startsession->setRequest($requestMock);

        $this->expectException(LocalizedException::class);
        $startsession->execute([
            'api_key' => 'klevu-1234567890',
            'store' => 'not_found_store_code',
        ]);
    }

    /**
     * @return void
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
    }

    /**
     * Loads store creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadStoreFixtures()
    {
        require __DIR__ . '/../../../_files/storeFixtures.php';
    }

    /**
     * Rolls back store creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadStoreFixturesRollback()
    {
        require __DIR__ . '/../../../_files/storeFixtures_rollback.php';
    }

    private function getRequestMock()
    {
        $requestMock = $this->getMockBuilder(\Klevu\Search\Model\Api\Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->method('setResponseModel')->willReturnSelf();
        $requestMock->method('setMethod')->willReturnSelf();
        $requestMock->method('setHeader')->willReturnSelf();

        return $requestMock;
    }
}
