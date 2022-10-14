<?php

namespace Klevu\Search\Test\Integration\Console\Command;

use Klevu\Search\Console\Command\RatingGeneration;
use Klevu\Search\Model\Attribute\Rating;
use Klevu\Search\Model\Attribute\ReviewCount;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RatingGenerationTest extends TestCase
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
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreInterface[]
     */
    private $stores = [];

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testRunWithoutArguments()
    {
        $this->setUpPhp5();

        $inputMock = $this->getInputMock();

        $outputMock = $this->getOutputMock();
        $outputMock->expects($this->once())
            ->method('writeln')
            ->with('<error>No option provided. Specify --regenerate option to recalculate the product rating</error>');

        $command = $this->objectManager->get(RatingGeneration::class);
        $status = $command->run($inputMock, $outputMock);

        $this->assertEquals(Cli::RETURN_FAILURE, $status);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testRegenerate()
    {
        $this->setUpPhp5();

        $store1 = $this->getStore('klevu_test_store_1');
        $store2 = $this->getStore('klevu_test_store_2');

        /** @var Product $simpleProduct1_store1 */
        $simpleProduct1_store1 = $this->productRepository->get('klevu_simple_1', true, $store1->getId());
        $simpleProduct1_store1->addAttributeUpdate(Rating::ATTRIBUTE_CODE, null, $store1->getId());
        $simpleProduct1_store1->addAttributeUpdate(ReviewCount::ATTRIBUTE_CODE, null, $store1->getId());

        /** @var Product $simpleProduct3_store2 */
        $simpleProduct3_store2 = $this->productRepository->get('klevu_simple_3', true, $store2->getId());
        $simpleProduct3_store2->addAttributeUpdate(Rating::ATTRIBUTE_CODE, null, $store2->getId());
        $simpleProduct3_store2->addAttributeUpdate(ReviewCount::ATTRIBUTE_CODE, null, $store2->getId());

        $this->productRepository->cleanCache();

        /** @var Product $simpleProduct1_store1 */
        $simpleProduct1_store1 = $this->productRepository->get('klevu_simple_1', false, $store1->getId());
        $this->assertEmpty($simpleProduct1_store1->getData(Rating::ATTRIBUTE_CODE),'Pre Run: Rating: Simple Product 1, Store 1');
        $this->assertEmpty($simpleProduct1_store1->getData(ReviewCount::ATTRIBUTE_CODE),'Pre Run: Review Count: Simple Product 1, Store 1');

        /** @var Product $simpleProduct3_store2 */
        $simpleProduct3_store2 = $this->productRepository->get('klevu_simple_3', false, $store2->getId());
        $this->assertEmpty($simpleProduct3_store2->getData(Rating::ATTRIBUTE_CODE),'Pre Run: Rating: Simple Product 3, Store 2');
        $this->assertEmpty($simpleProduct3_store2->getData(ReviewCount::ATTRIBUTE_CODE),'Pre Run: Review Count: Simple Product 3, Store 2');

        /** @var Product $simpleProductWithAttributes_store1 */
        $simpleProductWithAttributes_store1 = $this->productRepository->get('klevu_simple_with_rating_with_reviewcount_store1', false, $store1->getId());
        $this->assertEquals(50, $simpleProductWithAttributes_store1->getData(Rating::ATTRIBUTE_CODE), 'Pre Run: Rating: Simple Product (with attributes), Store 1');
        $this->assertEquals(10, $simpleProductWithAttributes_store1->getData(ReviewCount::ATTRIBUTE_CODE), 'Pre Run: Review Count: Simple Product (with attributes), Store 1');

        /** @var Product $simpleProductWithAttributes_store2 */
        $simpleProductWithAttributes_store2 = $this->productRepository->get('klevu_simple_with_rating_with_reviewcount_store2', false, $store2->getId());
        $this->assertEquals(90, $simpleProductWithAttributes_store2->getData(Rating::ATTRIBUTE_CODE), 'Pre Run: Rating: Simple Product (with attributes), Store 2');
        $this->assertEquals(2, $simpleProductWithAttributes_store2->getData(ReviewCount::ATTRIBUTE_CODE), 'Pre Run: Review Count: Simple Product (with attributes), Store 2');

        $this->productRepository->cleanCache();

        $inputMock = $this->getInputMock();
        $inputMock->method('hasParameterOption')
            ->with('--regenerate')
            ->willReturn(true);

        $outputMock = $this->getOutputMock();

        $command = $this->objectManager->get(RatingGeneration::class);
        $status = $command->run($inputMock, $outputMock);

        $this->assertEquals(Cli::RETURN_SUCCESS, $status);

        $simpleProduct1_store1 = $this->productRepository->get('klevu_simple_1', false, $store1->getId());
        $this->assertEquals(80, $simpleProduct1_store1->getData(Rating::ATTRIBUTE_CODE), 'Post Run: Rating: Simple Product 1, Store 1');
        $this->assertEquals(1, $simpleProduct1_store1->getData(ReviewCount::ATTRIBUTE_CODE), 'Post Run: Review Count: Simple Product 1, Store 1');

        /** @var Product $simpleProduct3_store2 */
        $simpleProduct3_store2 = $this->productRepository->get('klevu_simple_3', false, $store2->getId());
        $this->assertEquals(20, $simpleProduct3_store2->getData(Rating::ATTRIBUTE_CODE), 'Post Run: Rating: Simple Product 3, Store 2');
        $this->assertEquals(3, $simpleProduct3_store2->getData(ReviewCount::ATTRIBUTE_CODE), 'Post Run: Review Count: Simple Product 3, Store 2');

        /** @var Product $simpleProductWithAttributes_store1 */
        $simpleProductWithAttributes_store1 = $this->productRepository->get('klevu_simple_with_rating_with_reviewcount_store1', false, $store1->getId());
        $this->assertEmpty($simpleProductWithAttributes_store1->getData(Rating::ATTRIBUTE_CODE), 'Post Run: Rating: Simple Product (with attributes), Store 1');
        $this->assertEmpty($simpleProductWithAttributes_store1->getData(ReviewCount::ATTRIBUTE_CODE), 'Post Run: Review Count: Simple Product (with attributes), Store 1');

        /** @var Product $simpleProductWithAttributes_store2 */
        $simpleProductWithAttributes_store2 = $this->productRepository->get('klevu_simple_with_rating_with_reviewcount_store2', false, $store2->getId());
        $this->assertEmpty($simpleProductWithAttributes_store2->getData(Rating::ATTRIBUTE_CODE), 'Post Run: Rating: Simple Product (with attributes), Store 2');
        $this->assertEmpty($simpleProductWithAttributes_store2->getData(ReviewCount::ATTRIBUTE_CODE), 'Post Run: Review Count: Simple Product (with attributes), Store 2');
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testRegenerateClearsDefaultScopeValues()
    {
        $this->setUpPhp5();

        $store1 = $this->getStore('klevu_test_store_1');

        /** @var Product $simpleProduct3_store1 */
        $simpleProduct3_store1 = $this->productRepository->get('klevu_simple_without_rating_without_reviewcount_allstores', true, Store::DEFAULT_STORE_ID);
        $simpleProduct3_store1->addAttributeUpdate(Rating::ATTRIBUTE_CODE, 60, Store::DEFAULT_STORE_ID);
        $simpleProduct3_store1->addAttributeUpdate(ReviewCount::ATTRIBUTE_CODE, 5, Store::DEFAULT_STORE_ID);

        $this->productRepository->cleanCache();

        /** @var Product $simpleProduct3_store1 */
        $simpleProduct3_store0 = $this->productRepository->get('klevu_simple_without_rating_without_reviewcount_allstores', false, Store::DEFAULT_STORE_ID);
        $this->assertEquals(60, $simpleProduct3_store0->getData(Rating::ATTRIBUTE_CODE),'Pre Run: Rating: Simple Product 3, Store 0');
        $this->assertEquals(5, $simpleProduct3_store0->getData(ReviewCount::ATTRIBUTE_CODE),'Pre Run: Review Count: Simple Product 3, Store 0');

        /** @var Product $simpleProduct3_store1 */
        $simpleProduct3_store1 = $this->productRepository->get('klevu_simple_without_rating_without_reviewcount_allstores', false, $store1->getId());
        $this->assertEquals(60, $simpleProduct3_store1->getData(Rating::ATTRIBUTE_CODE),'Pre Run: Rating: Simple Product 3, Store 1');
        $this->assertEquals(5, $simpleProduct3_store1->getData(ReviewCount::ATTRIBUTE_CODE),'Pre Run: Review Count: Simple Product 3, Store 1');

        $this->productRepository->cleanCache();

        $inputMock = $this->getInputMock();
        $inputMock->method('hasParameterOption')
            ->with('--regenerate')
            ->willReturn(true);

        $outputMock = $this->getOutputMock();

        $command = $this->objectManager->get(RatingGeneration::class);
        $status = $command->run($inputMock, $outputMock);

        $this->assertEquals(Cli::RETURN_SUCCESS, $status);

        $simpleProduct3_store0 = $this->productRepository->get('klevu_simple_without_rating_without_reviewcount_allstores', false, Store::DEFAULT_STORE_ID);
        $this->assertNull($simpleProduct3_store0->getData(Rating::ATTRIBUTE_CODE), 'Post Run: Rating: Simple Product 3, Store 0');
        $this->assertEmpty($simpleProduct3_store0->getData(ReviewCount::ATTRIBUTE_CODE), 'Post Run: Review Count: Simple Product 3, Store 0');

        $simpleProduct3_store1 = $this->productRepository->get('klevu_simple_without_rating_without_reviewcount_allstores', false, $store1->getId());
        $this->assertNull($simpleProduct3_store1->getData(Rating::ATTRIBUTE_CODE), 'Post Run: Rating: Simple Product 3, Store 1');
        $this->assertEmpty($simpleProduct3_store1->getData(ReviewCount::ATTRIBUTE_CODE), 'Post Run: Review Count: Simple Product 3, Store 1');
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * @param $storeCode
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode)
    {
        if (!isset($this->stores[$storeCode])) {
            $this->stores[$storeCode] = $this->storeManager->getStore($storeCode);
        }

        return $this->stores[$storeCode];
    }

    /**
     * @return MockObject|InputInterface&MockObject
     */
    private function getInputMock()
    {
        return $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|OutputInterface&MockObject
     */
    private function getOutputMock()
    {
        return $this->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadReviewFixtures()
    {
        include __DIR__ . '/../../Service/Catalog/_files/reviewFixturesWithRating.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadReviewFixturesRollback()
    {
        include __DIR__ . '/../../Service/Catalog/_files/reviewFixturesWithRating_rollback.php';
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../Service/Catalog/_files/productFixtures.php';
        include __DIR__ . '/../../Service/Catalog/_files/productFixtures_ratingAttributes.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../Service/Catalog/_files/productFixtures_rollback.php';
        include __DIR__ . '/../../Service/Catalog/_files/productFixtures_ratingAttributes_rollback.php';
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }
}
