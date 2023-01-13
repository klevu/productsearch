<?php

use Magento\Bundle\Api\Data\LinkInterfaceFactory;
use Magento\Bundle\Api\Data\OptionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as IndexerProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Registry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\Eav\Model\Config as EavConfig;

include __DIR__ . '/productFixturesIncludeOos_rollback.php';
require __DIR__ . '/productAttributeFixtures.php';

$objectManager = Bootstrap::getObjectManager();

/** @var IndexerProcessor $indexerProcessor */
$indexerProcessor = $objectManager->get(IndexerProcessor::class);

/** @var Website $baseWebsite */
$baseWebsite = $objectManager->create(Website::class);
$baseWebsite->load('base', 'code');

/** @var Website $website1 */
$website1 = $objectManager->create(Website::class);
$website1->load('klevu_test_website_1', 'code');

/** @var Website $website2 */
$website2 = $objectManager->create(Website::class);
$website2->load('klevu_test_website_2', 'code');

/** @var EavConfig $eavConfig */
$eavConfig = $objectManager->get(EavConfig::class);
/** @var ConfigurableOptionsFactory $configurableOptionsFactory */
$configurableOptionsFactory = $objectManager->get(ConfigurableOptionsFactory::class);

$configurableAttribute = $eavConfig->getAttribute('catalog_product', 'klevu_test_configurable');
$configurableAttributeOptions = $configurableAttribute->getOptions();

/** @var ProductLinkInterfaceFactory $productLinkFactory */
$productLinkFactory = $objectManager->get(ProductLinkInterfaceFactory::class);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$fixtures = [
    // Simples
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_instock_visible',
        'name' => '[Klevu] Sync Test: Simple Product: In Stock; Visible',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 10.00,
        'special_price' => 4.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'klevu_test_configurable' => $configurableAttributeOptions[10]->getValue(),
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_synctest_instock_visible_' . crc32(rand()),
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_instock_notvisible',
        'name' => '[Klevu] Sync Test: Simple Product: In Stock; Not Visible',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 10.00,
        'special_price' => 4.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_synctest_instock_notvisible_' . crc32(rand()),
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_instock_vissearch',
        'name' => '[Klevu] Sync Test: Simple Product: In Stock; Visibility: Search',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 10.00,
        'special_price' => 4.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_IN_SEARCH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_synctest_instock_vissearch_' . crc32(rand()),
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_instock_viscatalog',
        'name' => '[Klevu] Sync Test: Simple Product: In Stock; Visibility: Catalog',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 10.00,
        'special_price' => 4.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_IN_CATALOG,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_synctest_instock_viscatalog_' . crc32(rand()),
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_oos_visible',
        'name' => '[Klevu] Sync Test: Simple Product: Out of Stock; Visible',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 10.00,
        'special_price' => 4.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 0,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_simple_synctest_oos_visible_' . crc32(rand()),
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_oos_notvisible',
        'name' => '[Klevu] Sync Test: Simple Product: Out of Stock; Not Visible',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 10.00,
        'special_price' => 4.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 0,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_simple_synctest_oos_notvisible_' . crc32(rand()),
    ],

    // Config Children
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_child_instock_1',
        'name' => '[Klevu] Sync Test: Child Product: In Stock 1',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 20.00,
        'special_price' => null,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_synctest_child_instock_1_' . crc32(rand()),
        'klevu_test_configurable' => $configurableAttributeOptions[1]->getValue(),
    ], [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_child_instock_2',
        'name' => '[Klevu] Sync Test: Child Product: In Stock 2',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 30.00,
        'special_price' => 7.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_synctest_child_instock_2_' . crc32(rand()),
        'klevu_test_configurable' => $configurableAttributeOptions[2]->getValue(),
    ], [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_child_oos',
        'name' => '[Klevu] Sync Test: Child Product: Out of Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 0.00,
        'special_price' => 2.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 0,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_simple_synctest_child_oos_' . crc32(rand()),
        'klevu_test_configurable' => $configurableAttributeOptions[3]->getValue(),
    ],

    // Configurable
    [
        'type_id' => 'configurable',
        'sku' => 'klevu_configurable_synctest_instock_childreninstock',
        'name' => '[Klevu] Sync Test: Configurable: In Stock; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => [
            $website1->getId(),
            $website2->getId(),
        ],
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_configurable_synctest_instock_childreninstock_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_instock_visible',
            'klevu_simple_synctest_child_instock_1',
            'klevu_simple_synctest_child_oos',
            'klevu_simple_synctest_child_instock_2',
        ],
    ],
    [
        'type_id' => 'configurable',
        'sku' => 'klevu_configurable_synctest_instock_childrenoos',
        'name' => '[Klevu] Sync Test: Configurable: In Stock; Children Out of Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => [
            $website1->getId(),
            $website2->getId(),
        ],
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_configurable_synctest_instock_childrenoos_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_child_oos',
        ],
    ],
    [
        'type_id' => 'configurable',
        'sku' => 'klevu_configurable_synctest_oos_childreninstock',
        'name' => '[Klevu] Sync Test: Configurable: Out of Stock; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => [
            $website1->getId(),
            $website2->getId(),
        ],
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_configurable_synctest_oos_childreninstock_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_child_instock_1',
            'klevu_simple_synctest_child_instock_2',
        ],
    ],
    [
        'type_id' => 'configurable',
        'sku' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
        'name' => '[Klevu] Sync Test: Configurable: In Stock; Not Visible; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => [
            $website1->getId(),
            $website2->getId(),
        ],
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_configurable_synctest_instock_notvisible_childreninstock_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_child_instock_1',
            'klevu_simple_synctest_child_instock_2',
        ],
    ],
    [
        'type_id' => 'configurable',
        'sku' => 'klevu_configurable_synctest_instock_vissearch_childreninstock',
        'name' => '[Klevu] Sync Test: Configurable: In Stock; Visibility: Search; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => [
            $website1->getId(),
            $website2->getId(),
        ],
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_IN_SEARCH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_configurable_synctest_instock_vissearch_childreninstock_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_child_instock_1',
            'klevu_simple_synctest_child_instock_2',
        ],
    ],
    [
        'type_id' => 'configurable',
        'sku' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
        'name' => '[Klevu] Sync Test: Configurable: In Stock; Visibility: Catalog; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => [
            $website1->getId(),
            $website2->getId(),
        ],
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_IN_CATALOG,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_child_instock_1',
            'klevu_simple_synctest_child_instock_2',
        ],
    ],

    // Grouped Children
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_groupchild_instock_1',
        'name' => '[Klevu] Sync Test: Grouped Child Product: In Stock 1',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 50.00,
        'special_price' => null,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_synctest_groupchild_instock_1_' . crc32(rand()),
    ], [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_groupchild_instock_2',
        'name' => '[Klevu] Sync Test: Grouped Child Product: In Stock 2',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 100.00,
        'special_price' => 25.50,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_synctest_groupchild_instock_2_' . crc32(rand()),
    ], [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_groupchild_oos',
        'name' => '[Klevu] Sync Test: Grouped Child Product: Out Of Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 250.00,
        'special_price' => 12.50,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 0,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_simple_synctest_groupchild_oos_' . crc32(rand()),
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_groupchild_disabled',
        'name' => '[Klevu] Sync Test: Child Product: Disabled',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 50.00,
        'special_price' => 22.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_DISABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_synctest_groupchild_disabled_' . crc32(rand()),
    ],


    // Grouped
    [
        'type_id' => 'grouped',
        'sku' => 'klevu_grouped_synctest_instock_childreninstock',
        'name' => '[Klevu] Sync Test: Grouped Product: In Stock; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 0.01,
        'special_price' => 0.01,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_grouped_synctest_instock_childreninstock_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_groupchild_instock_1',
            'klevu_simple_synctest_groupchild_instock_2',
            'klevu_simple_synctest_groupchild_oos',
        ],
    ],
    [
        'type_id' => 'grouped',
        'sku' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
        'name' => '[Klevu] Sync Test: Grouped Product: In Stock; Not Visible; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 0.01,
        'special_price' => 0.01,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_grouped_synctest_instock_notvisible_childreninstock_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_groupchild_instock_1',
            'klevu_simple_synctest_groupchild_instock_2',
            'klevu_simple_synctest_groupchild_oos',
        ],
    ],
    [
        'type_id' => 'grouped',
        'sku' => 'klevu_grouped_synctest_instock_childrenoos',
        'name' => '[Klevu] Sync Test: Grouped Product: In Stock; Children OOS',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 0.01,
        'special_price' => 0.01,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_grouped_synctest_instock_childrenoos_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_groupchild_oos',
        ],
    ],
    [
        'type_id' => 'grouped',
        'sku' => 'klevu_grouped_synctest_oos_childreninstock',
        'name' => '[Klevu] Sync Test: Grouped Product: OOS; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 0.01,
        'special_price' => 0.01,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 0,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_grouped_synctest_oos_childreninstock_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_groupchild_instock_1',
            'klevu_simple_synctest_groupchild_instock_2',
            'klevu_simple_synctest_groupchild_oos',
        ],
    ],
    [
        'type_id' => 'grouped',
        'sku' => 'klevu_grouped_synctest_instock_childrendisabled',
        'name' => '[Klevu] Sync Test: Grouped Product: In Stock; Children Disabled',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 0.01,
        'special_price' => 0.01,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 0,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_grouped_synctest_instock_childrendisabled_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_groupchild_disabled',
        ],
    ],

    // Bundle Children
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_bundlechild_instock_1',
        'name' => '[Klevu] Sync Test: Bundle Child Product: In Stock 1',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 50.00,
        'special_price' => null,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_synctest_bundlechild_instock_1_' . crc32(rand()),
    ], [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_bundlechild_instock_2',
        'name' => '[Klevu] Sync Test: Bundle Child Product: In Stock 2',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 100.00,
        'special_price' => 15.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_synctest_bundlechild_instock_2_' . crc32(rand()),
    ], [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_bundlechild_oos',
        'name' => '[Klevu] Sync Test: Bundle Child Product: Out of Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 150.00,
        'special_price' => 1.50,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 0,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_simple_synctest_bundlechild_oos_' . crc32(rand()),
    ],

    // Bundle
    [
        'type_id' => 'bundle',
        'sku' => 'klevu_bundle_synctest_instock_childreninstock',
        'name' => '[Klevu] Sync Test: Bundle Product: In Stock; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_bundle_synctest_instock_childreninstock_' . crc32(rand()),
        'price_type' => \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC,
        'child_skus' => [
            'klevu_simple_synctest_bundlechild_instock_1',
            'klevu_simple_synctest_bundlechild_instock_2',
            'klevu_simple_synctest_bundlechild_oos',
        ],
    ],
    [
        'type_id' => 'bundle',
        'sku' => 'klevu_bundle_synctest_instock_childreninstock_fixedprice',
        'name' => '[Klevu] Sync Test: Bundle Product: In Stock; Children In Stock (Fixed Price)',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 0.01,
        'special_price' => null,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_bundle_synctest_instock_childreninstock_fixedprice_' . crc32(rand()),
        'price_type' => \Magento\Bundle\Model\Product\Price::PRICE_TYPE_FIXED,
        'child_skus' => [
            'klevu_simple_synctest_bundlechild_instock_1',
            'klevu_simple_synctest_bundlechild_instock_2',
            'klevu_simple_synctest_bundlechild_oos',
        ],
    ],
    [
        'type_id' => 'bundle',
        'sku' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
        'name' => '[Klevu] Sync Test: Bundle Product: In Stock; Not Visible; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_bundle_synctest_instock_notvisible_childreninstock_' . crc32(rand()),
        'price_type' => \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC,
        'child_skus' => [
            'klevu_simple_synctest_bundlechild_instock_1',
            'klevu_simple_synctest_bundlechild_instock_2',
            'klevu_simple_synctest_bundlechild_oos',
        ],
    ],
    [
        'type_id' => 'bundle',
        'sku' => 'klevu_bundle_synctest_instock_childrenoos',
        'name' => '[Klevu] Sync Test: Bundle Product: In Stock; Children Out Of Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_bundle_synctest_instock_childrenoos_' . crc32(rand()),
        'price_type' => \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC,
        'child_skus' => [
            'klevu_simple_synctest_bundlechild_oos',
        ],
    ],
    [
        'type_id' => 'bundle',
        'sku' => 'klevu_bundle_synctest_oos_childreninstock',
        'name' => '[Klevu] Sync Test: Bundle Product: Out Of Stock; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 0,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_bundle_synctest_oos_childreninstock_' . crc32(rand()),
        'price_type' => \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC,
        'child_skus' => [
            'klevu_simple_synctest_bundlechild_instock_1',
            'klevu_simple_synctest_bundlechild_instock_2',
            'klevu_simple_synctest_bundlechild_oos',
        ],
    ],
];

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

$attributeValues = [];
$productSkuToId = [];
$configurableChildSkus = [];
foreach ($fixtures as $fixture) {
    if (!isset($fixture['child_skus']) || $fixture['type_id'] !== 'configurable') {
        continue;
    }

    $configurableChildSkus[] = $fixture['child_skus'];
}
$configurableChildSkus = array_filter(array_unique(array_merge([], ...$configurableChildSkus)));

$productIds = [];
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'simple') {
        continue;
    }

    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);

    $product = $productRepository->save($product);
    $productIds[] = $product->getId();

    $indexerProcessor->reindexRow($product->getId());

    if (in_array($fixture['sku'], $configurableChildSkus, true)) {
        if (!isset($fixture['klevu_test_configurable'])) {
            throw new \LogicException(sprintf(
                'Fixture "%s" used as a configurable variant does not contain "klevu_test_configurable"',
                $fixture['sku']
            ));
        }

        $attributeValues[$fixture['sku']] = [
            'label' => 'test',
            'attribute_id' => $configurableAttribute->getId(),
            'value_index' => $fixture['klevu_test_configurable'],
        ];
        $productSkuToId[$product->getSku()] = $product->getId();
    }
}

// Configurable Setup
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'configurable') {
        continue;
    }

    $childSkus = $fixture['child_skus'];
    unset($fixture['price'], $fixture['special_price'], $fixture['child_skus']);

    /** @var $product Product */
    $product = $objectManager->create(Product::class);

    $values = array_values(array_intersect_key(
        $attributeValues,
        array_fill_keys($childSkus, '')
    ));
    $associatedProductIds = array_values(array_intersect_key(
        $productSkuToId,
        array_fill_keys($childSkus, '')
    ));

    if ($values) {
        $configurableAttributesData = [
            [
                'attribute_id' => $configurableAttribute->getId(),
                'code' => $configurableAttribute->getAttributeCode(),
                'label' => $configurableAttribute->getDataUsingMethod('store_label'),
                'position' => 0,
                'values' => $values,
            ],
        ];
        $configurableOptions = $configurableOptionsFactory->create($configurableAttributesData);
        /** @var ProductExtensionInterface $extensionConfigurableAttributes */
        $extensionConfigurableAttributes = $product->getExtensionAttributes();
        $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);

        $product->setExtensionAttributes($extensionConfigurableAttributes);
    }

    $product->isObjectNew(true);
    $product->addData($fixture);

    $productRepository->cleanCache();
    $product = $productRepository->save($product);
    $productIds[] = $product->getId();
}

// Grouped Setup
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'grouped') {
        continue;
    }

    $childSkus = $fixture['child_skus'];

    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);

    $product = $productRepository->save($product);
    $productRepository->cleanCache();

    $simpleProductLinks = [];
    foreach ($childSkus as $i => $childSku) {
        $simpleProductLink = $productLinkFactory->create();
        $simpleProductLink->setSku($fixture['sku']);
        $simpleProductLink->setLinkType('associated');
        $simpleProductLink->setLinkedProductSku($childSku);
        $simpleProductLink->setLinkedProductType('simple');
        $simpleProductLink->setPosition($i + 1);
        $extensionAttributes = $simpleProductLink->getExtensionAttributes();
        $extensionAttributes->setQty(1);

        $simpleProductLinks[] = $simpleProductLink;
    }

    $product->setProductLinks($simpleProductLinks);
    $indexerProcessor->reindexRow($product->getId());

    $product = $productRepository->save($product);
    $productRepository->cleanCache();
    $productIds[] = $product->getId();
}

// Bundle Setup
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'bundle') {
        continue;
    }

    $childSkus = $fixture['child_skus'];

    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);

    $product->setPriceView(1);
    $product->setSkuType(1);
    $product->setWeightType(1);
    $product->setPriceType($fixture['price_type']);
    $product->setShipmentType(0);
    if (isset($fixture['price'])) {
        $product->setPrice($fixture['price']);
    }
    $product->setBundleOptionsData(
        [
            [
                'title' => 'Bundle Product Items',
                'default_title' => 'Bundle Product Items',
                'type' => 'select',
                'required' => 1,
                'delete' => '',
            ],
        ]
    );

    $bundleSelectionsData = [];
    foreach ($childSkus as $childSku) {
        $childProduct = $productRepository->get($childSku);
        $bundleSelectionsData[] = [
            'product_id' => $childProduct->getId(),
            'selection_price_value' => $childProduct->getPrice(),
            'selection_qty' => 1,
            'selection_can_change_qty' => 1,
            'delete' => '',
        ];
    }
    $product->setBundleSelectionsData([$bundleSelectionsData]);

    if ($product->getBundleOptionsData()) {
        $options = [];
        $optionFactory = $objectManager->create(OptionInterfaceFactory::class);
        $linkFactory = $objectManager->create(LinkInterfaceFactory::class);
        foreach ($product->getBundleOptionsData() as $key => $optionData) {
            if ((bool)$optionData['delete']) {
                continue;
            }

            $option = $optionFactory->create(['data' => $optionData]);
            $option->setSku($product->getSku());
            $option->setOptionId(null);

            $links = [];
            $bundleLinks = $product->getBundleSelectionsData();
            if (empty($bundleLinks[$key])) {
                continue;
            }

            foreach ($bundleLinks[$key] as $linkData) {
                if ((bool)$linkData['delete']) {
                    continue;
                }

                $linkProduct = $productRepository->getById($linkData['product_id']);

                $link = $linkFactory->create(['data' => $linkData]);
                $link->setSku($linkProduct->getSku());
                $link->setQty($linkData['selection_qty']);
                $link->setPrice($linkData['selection_price_value']);
                if (isset($linkData['selection_can_change_qty'])) {
                    $link->setCanChangeQuantity($linkData['selection_can_change_qty']);
                }
                $links[] = $link;
            }

            $option->setProductLinks($links);
            $options[] = $option;
        }

        $extensionAttributes = $product->getExtensionAttributes();
        $extensionAttributes->setBundleProductOptions($options);
        $product->setExtensionAttributes($extensionAttributes);
    }

    $product = $productRepository->save($product);
    $productIds[] = $product->getId();
    $productRepository->cleanCache();
}

$indexerFactory = $objectManager->get(IndexerFactory::class);
$indexes = [
    'catalog_product_attribute',
    'catalog_product_price',
    'inventory',
    'cataloginventory_stock',
];
foreach ($indexes as $index) {
    $indexer = $indexerFactory->create();
    try {
        $indexer->load($index);
        $indexer->reindexAll();
    } catch (\InvalidArgumentException $e) {
        // Support for older versions of Magento which may not have all indexers
        continue;
    }
}

$productRepository->cleanCache();

/** @var \Magento\CatalogInventory\Model\StockRegistryStorage $stockRegistryStorage */
$stockRegistryStorage = $objectManager->get(\Magento\CatalogInventory\Model\StockRegistryStorage::class);
foreach ($productIds as $productId) {
    $stockRegistryStorage->removeStockStatus($productId);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
