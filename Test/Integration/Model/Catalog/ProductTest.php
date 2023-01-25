<?php

namespace KlevuAlias\Search\Test\Integration\Model\Catalog;

use Klevu\Search\Model\Catalog\Product as KlevuProductModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuResourceModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Model\Entity;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testGetIdReturnsEntityIdForProduct()
    {
        $this->setUpPhp5();

        $product = $this->instantiateKlevuProductModel();

        $expected = '0-0';
        $this->assertSame($expected, $product->getId());
    }

    public function testGetIdReturnsEntityIdForProductWithProductId()
    {
        $this->setUpPhp5();

        $product = $this->instantiateKlevuProductModel();
        $product->setData('product_id', 1);
        $productId = $product->getData('product_id');

        $expected = $productId . '-0';
        $this->assertSame($expected, $product->getId());
    }

    public function testGetIdReturnsEntityIdForProductWithProductIdAndParentId()
    {
        $this->setUpPhp5();

        $product = $this->instantiateKlevuProductModel();
        $product->setData('product_id', 1);
        $productId = $product->getData('product_id');
        $product->setData('parent_id', 2);
        $parentId = $product->getData('parent_id');

        $expected = $productId . '-' . $parentId;
        $this->assertSame($expected, $product->getId());
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
     * @return KlevuProductModel;
     */
    private function instantiateKlevuProductModel()
    {
        return $this->objectManager->get(KlevuProductModel::class);
    }
}
