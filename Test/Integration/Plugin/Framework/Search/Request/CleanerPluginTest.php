<?php

namespace Klevu\Search\Test\Integration\Plugin\Framework\Search\Request;

use Klevu\Search\Model\Api\Magento\Request\Product as ProductRequestApi;
use Klevu\Search\Model\Api\Magento\Request\ProductInterface as ProductRequestApiInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController;

class CleanerPluginTest extends AbstractController
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ProductRepository
     */
    private $productRepositoryMock;

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadFixtures()
    {
        require __DIR__ . '/_files/product_simple_cleaner.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadFixturesRollback()
    {
        require __DIR__ . '/_files/product_simple_cleaner_rollback.php';
    }

    /**
     * Ref: KS-5825
     *
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadFixtures
     */
    public function testSearchCleanerFoundResults()
    {
        $this->setupPhp5();
        $product = $this->productRepository->get('424756');
        $productRequestMock = $this->getProductRequestMock([(int)$product->getId()]);

        //injecting to current instance of object mgr
        $this->objectManager->addSharedInstance($productRequestMock, ProductRequestApiInterface::class);
        $this->objectManager->addSharedInstance($productRequestMock, ProductRequestApi::class);

        $this->dispatch('catalogsearch/result/?q=6mill');
        $responseBody = $this->getResponse()->getBody();
        $this->assertStringContainsString($product->getName(), $responseBody);
        $this->assertStringNotContainsString('Your search returned no results.', $responseBody);
    }

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadFixtures
     */
    public function testSearchCleanerNotFoundResults()
    {
        $this->setupPhp5();
        $product = $this->productRepository->get('424756');
        $productRequestMock = $this->getProductRequestMock([]);

        $this->objectManager->addSharedInstance($productRequestMock, ProductRequestApiInterface::class);
        $this->objectManager->addSharedInstance($productRequestMock, ProductRequestApi::class);

        $this->dispatch('catalogsearch/result/?q=Rich');
        $responseBody = $this->getResponse()->getBody();
        $this->assertStringNotContainsString($product->getName(), $responseBody);
        $this->assertStringContainsString('Your search returned no results.', $responseBody);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
    }

    /**
     * @param array $productIds
     * @return ProductRequestApiInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getProductRequestMock(array $productIds)
    {
        $productRequestMock = $this->getMockBuilder(ProductRequestApiInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $productRequestMock->expects($this->any())
            ->method('_getKlevuProductIds')
            ->willReturn($productIds);
        return $productRequestMock;
    }
}
