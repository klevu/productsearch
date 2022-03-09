<?php

namespace Klevu\Search\Test\Integration\Model\Product;

use Klevu\Search\Model\Product\Product as ProductModel;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
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
