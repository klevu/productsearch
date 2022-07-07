<?php

namespace Klevu\Search\Test\Integration\Service\Account;

use Klevu\Search\Api\Service\Account\GetStoresUsingApiKeysInterface;
use Klevu\Search\Exception\InvalidApiKeyException;
use Klevu\Search\Service\Account\GetStoresUsingApiKeys;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetStoresUsingApiKeysTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();
        $service = $this->instantiateService();

        $this->assertInstanceOf(GetStoresUsingApiKeysInterface::class, $service);
    }

    /**
     * @dataProvider missingApiKeysDataProvider
     */
    public function testExceptionIsThrownIfInputFieldsAreMissing($restApiKey, $jsApiKey)
    {
        $this->setUpPhp5();

        $this->expectException(InvalidApiKeyException::class);
        $this->expectExceptionCode(400);

        $service = $this->instantiateService();
        $service->execute($restApiKey, $jsApiKey);
    }

    public function testReturnsEmptyArrayWhenNotIntegrated()
    {
        $this->setUpPhp5();

        $restApiKey = 'klevu-rest-api-key';
        $jsApiKey = 'klevu-js-api-key';

        $service = $this->instantiateService();
        $stores = $service->execute($restApiKey, $jsApiKey);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($stores);
        } else {
            $this->assertTrue(is_array($stores), 'Is Array');
        }
        $this->assertCount(0, $stores);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     */
    public function testReturnsArrayOfStoresWhenKeyIsAlreadyInUse()
    {
        $this->setUpPhp5();

        $restApiKey = 'klevu-rest-api-key';
        $jsApiKey = 'klevu-js-api-key';

        $service = $this->instantiateService();
        $stores = $service->execute($restApiKey, $jsApiKey);

        $currentStore = $this->getStore();

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($stores);
        } else {
            $this->assertTrue(is_array($stores), 'Is Array');
        }
        $this->assertCount(1, $stores);
        $this->assertSame($currentStore->getId(), $stores[0]['id']);
        $this->assertSame($currentStore->getCode(), $stores[0]['code']);
        $this->assertSame($currentStore->getName(), $stores[0]['name']);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return array[]
     */
    public function missingApiKeysDataProvider()
    {
        return [
            [null, 'klevu-js-api-key'],
            ['klevu-reat-api-key', null],
            [null, null],
            ['tooShort', 'klevu-js-api-key'],
            ['klevu-reat-api-key', 'wrong-format']
        ];
    }

    /**
     * @return GetStoresUsingApiKeys
     */
    private function instantiateService()
    {
        return $this->objectManager->create(GetStoresUsingApiKeys::class);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode = 'klevu_test_store_1')
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }
}
