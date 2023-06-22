<?php

namespace Klevu\Search\Test\Integration\Model\Product;

use Klevu\Search\Model\Product\Product as ProductModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CategoryCollection
     */
    private $categoryCollection;

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @return void
     */
    public function testGetCategoryPaths_TopLevel_Enabled()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $actualResult = $productModel->getCategoryPaths();
        $expectedResults = [
            [
                '[Klevu][Product Test 1] Top Level: Enabled',
            ]
        ];
        $notExpectedResults = [];

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($actualResult);
        } else {
            $this->assertTrue(is_array($actualResult), 'Is Array');
        }
        foreach ($expectedResults as $expectedResult) {
            $this->assertTrue(
                in_array($expectedResult, $actualResult, true),
                sprintf('Expected result: %s', json_encode($expectedResult))
            );
        }
        foreach ($notExpectedResults as $notExpectedResult) {
            $this->assertFalse(
                in_array($notExpectedResult, $actualResult, true),
                sprintf('Not Expected result: %s', json_encode($notExpectedResult))
            );
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @return void
     */
    public function testGetCategoryPaths_TopLevel_Disabled()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $actualResult = $productModel->getCategoryPaths();
        $expectedResults = [
        ];
        $notExpectedResults = [
            [
                '[Klevu][Product Test 2] Top Level: Disabled',
            ],
        ];

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($actualResult);
        } else {
            $this->assertTrue(is_array($actualResult), 'Is Array');
        }
        foreach ($expectedResults as $expectedResult) {
            $this->assertTrue(
                in_array($expectedResult, $actualResult, true),
                sprintf('Expected result: %s', json_encode($expectedResult))
            );
        }
        foreach ($notExpectedResults as $notExpectedResult) {
            $this->assertFalse(
                in_array($notExpectedResult, $actualResult, true),
                sprintf('Not Expected result: %s', json_encode($notExpectedResult))
            );
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @return void
     */
    public function testGetCategoryPaths_Nested_AllEnabled()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $actualResult = $productModel->getCategoryPaths();
        $expectedResults = [
            [
                '[Klevu][Product Test 3] Nested Parent: Enabled',
            ], [
                '[Klevu][Product Test 3] Nested Parent: Enabled',
                '[Klevu][Product Test 3] Nested Child: Enabled',
            ],
        ];
        $notExpectedResults = [
            [
                '[Klevu][Product Test 3] Nested Child: Enabled',
            ],
        ];

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($actualResult);
        } else {
            $this->assertTrue(is_array($actualResult), 'Is Array');
        }
        foreach ($expectedResults as $expectedResult) {
            $this->assertTrue(
                in_array($expectedResult, $actualResult, true),
                sprintf('Expected result: %s', json_encode($expectedResult))
            );
        }
        foreach ($notExpectedResults as $notExpectedResult) {
            $this->assertFalse(
                in_array($notExpectedResult, $actualResult, true),
                sprintf('Not Expected result: %s', json_encode($notExpectedResult))
            );
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @return void
     */
    public function testGetCategoryPaths_Nested_ParentDisabled()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $actualResult = $productModel->getCategoryPaths();
        $expectedResults = [
            [
                '[Klevu][Product Test 4] Nested Parent: Disabled',
                '[Klevu][Product Test 4] Nested Child: Enabled',
            ],
        ];
        $notExpectedResults = [
            [
                '[Klevu][Product Test 4] Nested Parent: Disabled',
            ], [
                '[Klevu][Product Test 4] Nested Child: Enabled',
            ],
        ];

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($actualResult);
        } else {
            $this->assertTrue(is_array($actualResult), 'Is Array');
        }
        foreach ($expectedResults as $expectedResult) {
            $this->assertTrue(
                in_array($expectedResult, $actualResult, true),
                sprintf('Expected result: %s', json_encode($expectedResult))
            );
        }
        foreach ($notExpectedResults as $notExpectedResult) {
            $this->assertFalse(
                in_array($notExpectedResult, $actualResult, true),
                sprintf('Not Expected result: %s', json_encode($notExpectedResult))
            );
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @return void
     */
    public function testGetCategoryPaths_Nested_ChildDisabled()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $actualResult = $productModel->getCategoryPaths();
        $expectedResults = [
            [
                '[Klevu][Product Test 5] Nested Parent: Enabled',
            ],
        ];
        $notExpectedResults = [
            [
                '[Klevu][Product Test 5] Nested Parent: Enabled',
                '[Klevu][Product Test 5] Nested Child: Disabled',
            ], [
                '[Klevu][Product Test 5] Nested Child: Disabled',
            ]
        ];

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($actualResult);
        } else {
            $this->assertTrue(is_array($actualResult), 'Is Array');
        }
        foreach ($expectedResults as $expectedResult) {
            $this->assertTrue(
                in_array($expectedResult, $actualResult, true),
                sprintf('Expected result: %s', json_encode($expectedResult))
            );
        }
        foreach ($notExpectedResults as $notExpectedResult) {
            $this->assertFalse(
                in_array($notExpectedResult, $actualResult, true),
                sprintf('Not Expected result: %s', json_encode($notExpectedResult))
            );
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetCategory_Configurable_ParentHasCategories()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $parentProduct = $this->productRepository->get('klevu-category-paths-test-parent-with-categories');
        $childProduct = $this->productRepository->get('klevu-category-paths-test-child-no-categories');

        $expectedResult =
            "[Klevu][Product Test 1] Top Level: Enabled;" .
            "[Klevu][Product Test 3] Nested Parent: Enabled;" .
            "[Klevu][Product Test 5] Nested Parent: Enabled;" .
            "[Klevu][Product Test 3] Nested Child: Enabled;" .
            "[Klevu][Product Test 4] Nested Child: Enabled";
        $actualResult = $productModel->getCategory($parentProduct, $childProduct);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetCategory_Configurable_ChildHasCategories()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $parentProduct = $this->productRepository->get('klevu-category-paths-test-parent-no-categories');
        $childProduct = $this->productRepository->get('klevu-category-paths-test-child-with-categories');

        $expectedResult = "";
        $actualResult = $productModel->getCategory($parentProduct, $childProduct);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetCategory_WithoutParent()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $product = $this->productRepository->get('klevu-category-paths-test-standalone');

        $expectedResult =
            "[Klevu][Product Test 1] Top Level: Enabled;" .
            "[Klevu][Product Test 3] Nested Parent: Enabled;" .
            "[Klevu][Product Test 5] Nested Parent: Enabled;" .
            "[Klevu][Product Test 3] Nested Child: Enabled;" .
            "[Klevu][Product Test 4] Nested Child: Enabled";
        $actualResult = $productModel->getCategory(null, $product);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetListCategory_Configurable_ParentHasCategories()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $parentProduct = $this->productRepository->get('klevu-category-paths-test-parent-with-categories');
        $childProduct = $this->productRepository->get('klevu-category-paths-test-child-no-categories');

        $expectedResult = [
            'KLEVU_PRODUCT',
            '[Klevu][Product Test 1] Top Level: Enabled',
            '[Klevu][Product Test 3] Nested Parent: Enabled',
            '[Klevu][Product Test 5] Nested Parent: Enabled',
            '[Klevu][Product Test 3] Nested Parent: Enabled;[Klevu][Product Test 3] Nested Child: Enabled',
            '[Klevu][Product Test 4] Nested Parent: Disabled;[Klevu][Product Test 4] Nested Child: Enabled',
        ];
        $actualResult = $productModel->getListCategory($parentProduct, $childProduct);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetListCategory_Configurable_ChildHasCategories()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $parentProduct = $this->productRepository->get('klevu-category-paths-test-parent-no-categories');
        $childProduct = $this->productRepository->get('klevu-category-paths-test-child-with-categories');

        $expectedResult = [
            'KLEVU_PRODUCT',
        ];
        $actualResult = $productModel->getListCategory($parentProduct, $childProduct);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetListCategory_WithoutParent()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $product = $this->productRepository->get('klevu-category-paths-test-standalone');

        $expectedResult = [
            'KLEVU_PRODUCT',
            '[Klevu][Product Test 1] Top Level: Enabled',
            '[Klevu][Product Test 3] Nested Parent: Enabled',
            '[Klevu][Product Test 5] Nested Parent: Enabled',
            '[Klevu][Product Test 3] Nested Parent: Enabled;[Klevu][Product Test 3] Nested Child: Enabled',
            '[Klevu][Product Test 4] Nested Parent: Disabled;[Klevu][Product Test 4] Nested Child: Enabled',
        ];
        $actualResult = $productModel->getListCategory(null, $product);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetAllCategoryId_Configurable_ParentHasCategories()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $parentProduct = $this->productRepository->get('klevu-category-paths-test-parent-with-categories');
        $childProduct = $this->productRepository->get('klevu-category-paths-test-child-no-categories');

        $expectedResult = implode(';', [
            $this->getCategoryIdByName("[Klevu][Product Test 1] Top Level: Enabled"),
            $this->getCategoryIdByName("[Klevu][Product Test 3] Nested Parent: Enabled"),
            $this->getCategoryIdByName("[Klevu][Product Test 5] Nested Parent: Enabled"),
            $this->getCategoryIdByName("[Klevu][Product Test 3] Nested Child: Enabled"),
            $this->getCategoryIdByName("[Klevu][Product Test 4] Nested Child: Enabled"),
        ]);
        $actualResult = $productModel->getAllCategoryId($parentProduct, $childProduct);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetAllCategoryId_Configurable_ChildHasCategories()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $parentProduct = $this->productRepository->get('klevu-category-paths-test-parent-no-categories');
        $childProduct = $this->productRepository->get('klevu-category-paths-test-child-with-categories');

        $expectedResult = "";
        $actualResult = $productModel->getAllCategoryId($parentProduct, $childProduct);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetAllCategoryId_WithoutParent()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $product = $this->productRepository->get('klevu-category-paths-test-standalone');

        $expectedResult = implode(';', [
            $this->getCategoryIdByName("[Klevu][Product Test 1] Top Level: Enabled"),
            $this->getCategoryIdByName("[Klevu][Product Test 3] Nested Parent: Enabled"),
            $this->getCategoryIdByName("[Klevu][Product Test 5] Nested Parent: Enabled"),
            $this->getCategoryIdByName("[Klevu][Product Test 3] Nested Child: Enabled"),
            $this->getCategoryIdByName("[Klevu][Product Test 4] Nested Child: Enabled"),
        ]);
        $actualResult = $productModel->getAllCategoryId(null, $product);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetAllCategoryPaths_Configurable_ParentHasCategories()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $parentProduct = $this->productRepository->get('klevu-category-paths-test-parent-with-categories');
        $childProduct = $this->productRepository->get('klevu-category-paths-test-child-no-categories');

        $expectedResult = implode(';;', [
            '[Klevu][Product Test 1] Top Level: Enabled::' . $this->getCategoryIdByName('[Klevu][Product Test 1] Top Level: Enabled'),
            '[Klevu][Product Test 3] Nested Parent: Enabled::' . $this->getCategoryIdByName('[Klevu][Product Test 3] Nested Parent: Enabled'),
            '[Klevu][Product Test 5] Nested Parent: Enabled::' . $this->getCategoryIdByName('[Klevu][Product Test 5] Nested Parent: Enabled'),
            '[Klevu][Product Test 3] Nested Parent: Enabled;[Klevu][Product Test 3] Nested Child: Enabled::'
            . $this->getCategoryIdByName('[Klevu][Product Test 3] Nested Parent: Enabled') . '/' . $this->getCategoryIdByName('[Klevu][Product Test 3] Nested Child: Enabled'),
            '[Klevu][Product Test 4] Nested Parent: Disabled;[Klevu][Product Test 4] Nested Child: Enabled::'
            . $this->getCategoryIdByName('[Klevu][Product Test 4] Nested Parent: Disabled') . '/' . $this->getCategoryIdByName('[Klevu][Product Test 4] Nested Child: Enabled'),
        ]);
        $actualResult = $productModel->getAllCategoryPaths($parentProduct, $childProduct);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetAllCategoryPaths_Configurable_ChildHasCategories()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $parentProduct = $this->productRepository->get('klevu-category-paths-test-parent-no-categories');
        $childProduct = $this->productRepository->get('klevu-category-paths-test-child-with-categories');

        $expectedResult = "";
        $actualResult = $productModel->getAllCategoryPaths($parentProduct, $childProduct);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetAllCategoryPaths_WithoutParent()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $product = $this->productRepository->get('klevu-category-paths-test-standalone');

        $expectedResult = implode(';;', [
            '[Klevu][Product Test 1] Top Level: Enabled::' . $this->getCategoryIdByName('[Klevu][Product Test 1] Top Level: Enabled'),
            '[Klevu][Product Test 3] Nested Parent: Enabled::' . $this->getCategoryIdByName('[Klevu][Product Test 3] Nested Parent: Enabled'),
            '[Klevu][Product Test 5] Nested Parent: Enabled::' . $this->getCategoryIdByName('[Klevu][Product Test 5] Nested Parent: Enabled'),
            '[Klevu][Product Test 3] Nested Parent: Enabled;[Klevu][Product Test 3] Nested Child: Enabled::'
            . $this->getCategoryIdByName('[Klevu][Product Test 3] Nested Parent: Enabled') . '/' . $this->getCategoryIdByName('[Klevu][Product Test 3] Nested Child: Enabled'),
            '[Klevu][Product Test 4] Nested Parent: Disabled;[Klevu][Product Test 4] Nested Child: Enabled::'
            . $this->getCategoryIdByName('[Klevu][Product Test 4] Nested Parent: Disabled') . '/' . $this->getCategoryIdByName('[Klevu][Product Test 4] Nested Child: Enabled'),
        ]);
        $actualResult = $productModel->getAllCategoryPaths(null, $product);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/currency/options/base USD
     * @magentoConfigFixture default/currency/options/default JPY
     * @magentoConfigFixture default/currency/options/allow JPY,EUR,GBP
     * @magentoConfigFixture default_store currency/options/default JPY
     * @magentoConfigFixture default_store currency/options/allow JPY,EUR,GBP
     * @magentoConfigFixture klevu_test_store_1_store currency/options/default GBP
     * @magentoConfigFixture klevu_test_store_1_store currency/options/allow GBP
     * @magentoConfigFixture klevu_test_store_2_store currency/options/default EUR
     * @magentoConfigFixture klevu_test_store_2_store currency/options/allow EUR
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetCurrency_DefaultStore()
    {
        $this->setupPhp5();

        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $expectedResult = 'USD';
        $actualResult = $productModel->getCurrency();

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/currency/options/base USD
     * @magentoConfigFixture default/currency/options/default JPY
     * @magentoConfigFixture default/currency/options/allow JPY,EUR,GBP
     * @magentoConfigFixture default_store currency/options/default JPY
     * @magentoConfigFixture default_store currency/options/allow JPY,EUR,GBP
     * @magentoConfigFixture klevu_test_store_1_store currency/options/default GBP
     * @magentoConfigFixture klevu_test_store_1_store currency/options/allow GBP
     * @magentoConfigFixture klevu_test_store_2_store currency/options/default EUR
     * @magentoConfigFixture klevu_test_store_2_store currency/options/allow EUR
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetCurrency_NonDefaultStore()
    {
        $this->setupPhp5();

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore('klevu_test_store_2');

        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $expectedResult = 'USD';
        $actualResult = $productModel->getCurrency();

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetGroupPricesDataReturnsNullIfNotGroupedPricesAreSet()
    {
        $this->setupPhp5();

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore('klevu_test_store_2');

        $mockProductBuilder = $this->getMockBuilder(\Magento\Catalog\Model\Product::class);
        $mockProduct = $mockProductBuilder->disableOriginalConstructor()->getMock();
        $mockProduct->expects($this->once())
            ->method('getData')
            ->with('tier_price')
            ->willReturn([]);

        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);
        $groupPrices = $productModel->getGroupPricesData($mockProduct);

        $this->assertNull($groupPrices);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetGroupPricesDataReturnsArray()
    {
        $this->setupPhp5();

        $tierPrice1 = [
            'all_groups' => '0',
            'cust_group' => '0',
            'percentage_value' => null,
            'price' => 11.0,
            'price_id' => '1',
            'price_qty' => '1.0000',
            'product_id' => '1',
            'website_id' => '0',
            'website_price' => 11.0
        ];
        $tierPrice2 = [
            'all_groups' => '0',
            'cust_group' => '1',
            'percentage_value' => null,
            'price' => 10.0,
            'price_id' => '2',
            'price_qty' => '1.0000',
            'product_id' => '1',
            'website_id' => '0',
            'website_price' => 10.0
        ];
        $tierPrice3 = [
            'all_groups' => '0',
            'cust_group' => '2',
            'percentage_value' => null,
            'price' => 9.0,
            'price_id' => '3',
            'price_qty' => '1.0000',
            'product_id' => '1',
            'website_id' => '0',
            'website_price' => 9.0
        ];

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore('klevu_test_store_2');

        $mockProductBuilder = $this->getMockBuilder(\Magento\Catalog\Model\Product::class);
        $mockProduct = $mockProductBuilder->disableOriginalConstructor()->getMock();
        $mockProduct->expects($this->once())
            ->method('getData')
            ->with('tier_price')
            ->willReturn([$tierPrice1, $tierPrice2, $tierPrice3]);

        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);
        $groupPrices = $productModel->getGroupPricesData($mockProduct);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($groupPrices);
        } else {
            $this->assertTrue(is_array($groupPrices), 'Is Array');
        }

        $this->assertArrayHasKey('0', $groupPrices);
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($groupPrices['0']);
        } else {
            $this->assertTrue(is_array($groupPrices['0']), 'Is Array');
        }
        $this->assertArrayHasKey('label', $groupPrices['0']);
        $this->assertArrayHasKey('values', $groupPrices['0']);
        $this->assertSame($tierPrice1['website_price'], $groupPrices['0']['values']);

        $this->assertArrayHasKey('1', $groupPrices);
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($groupPrices['1']);
        } else {
            $this->assertTrue(is_array($groupPrices['1']), 'Is Array');
        }
        $this->assertArrayHasKey('label', $groupPrices['1']);
        $this->assertArrayHasKey('values', $groupPrices['1']);
        $this->assertSame($tierPrice2['website_price'], $groupPrices['1']['values']);

        $this->assertArrayHasKey('2', $groupPrices);
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($groupPrices['2']);
        } else {
            $this->assertTrue(is_array($groupPrices['2']), 'Is Array');
        }
        $this->assertArrayHasKey('label', $groupPrices['2']);
        $this->assertArrayHasKey('values', $groupPrices['2']);
        $this->assertSame($tierPrice3['website_price'], $groupPrices['2']['values']);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
        $this->categoryCollection = null;
    }

    /**
     * Loads category fixtures scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadCategoriesForCategoryPathsFixtures()
    {
        require __DIR__ . '/_files/categoriesForCategoryPathsFixtures.php';
    }

    /**
     * Rolls back category fixtures scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadCategoriesForCategoryPathsFixturesRollback()
    {
        require __DIR__ . '/_files/categoriesForCategoryPathsFixtures_rollback.php';
    }

    /**
     * Loads product fixtures scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductsForCategoryPathsFixtures()
    {
        require __DIR__ . '/_files/productsForCategoryPathsFixtures.php';
    }

    /**
     * Rolls back product fixtures scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductsForCategoryPathsFixturesRollback()
    {
        require __DIR__ . '/_files/productsForCategoryPathsFixtures_rollback.php';
    }

    /**
     * Loads website fixtures scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website fixtures scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }

    /**
     * @param string $categoryName
     * @return int|null
     */
    private function getCategoryIdByName($categoryName)
    {
        $categoryCollection = $this->getCategoryCollection();
        $category = current($categoryCollection->getItemsByColumnValue('name', $categoryName));

        return $category ? (int)$category->getId() : null;
    }

    /**
     * @return CategoryCollection
     */
    private function getCategoryCollection()
    {
        if (null === $this->categoryCollection) {
            $this->categoryCollection = $this->objectManager->create(CategoryCollection::class);
            $this->categoryCollection->addAttributeToFilter('name', ['like' => '[Klevu][Product Test %']);
            $this->categoryCollection->load();
        }

        return $this->categoryCollection;
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @magentoConfigFixture klevu_search/attributes/categoryanchor 1
     * @magentoConfigFixture default_store klevu_search/attributes/categoryanchor 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/attributes/categoryanchor 1
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetLongestPathCategoryName_WithAnchorEnabled()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $product = $this->productRepository->get('klevu-category-paths-test-standalone');

        $actualResult = $productModel->getCategory(null, $product);
        $expectedResults = '[Klevu][Product Test 1] Top Level: Enabled;[Klevu][Product Test 3] Nested Parent: Enabled;[Klevu][Product Test 5] Nested Parent: Enabled;[Klevu][Product Test 3] Nested Child: Enabled;[Klevu][Product Test 4] Nested Child: Enabled';

        $this->assertSame($expectedResults, $actualResult);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadCategoriesForCategoryPathsFixtures
     * @magentoDataFixture loadProductsForCategoryPathsFixtures
     * @magentoConfigFixture klevu_search/attributes/categoryanchor 0
     * @magentoConfigFixture default_store klevu_search/attributes/categoryanchor 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/attributes/categoryanchor 0
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetLongestPathCategoryName_WithAnchorDisabled()
    {
        $this->setupPhp5();
        /** @var ProductModel $productModel */
        $productModel = $this->objectManager->create(ProductModel::class);

        $product = $this->productRepository->get('klevu-category-paths-test-standalone');

        $actualResult = $productModel->getCategory(null, $product);
        $expectedResults =
            '[Klevu][Product Test 1] Top Level: Enabled;'
            . '[Klevu][Product Test 3] Nested Parent: Enabled;'
            . '[Klevu][Product Test 5] Nested Parent: Enabled;'
            . '[Klevu][Product Test 3] Nested Child: Enabled;'
            . '[Klevu][Product Test 4] Nested Child: Enabled';

        $this->assertSame($expectedResults, $actualResult);
    }
}
