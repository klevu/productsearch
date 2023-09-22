<?php

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\TestFramework\Helper\Bootstrap;

include __DIR__ . '/categoriesForCategoryPathsFixtures_rollback.php';

$objectManager = Bootstrap::getObjectManager();

/** @var CategoryRepositoryInterface $categoryRepository */
$categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);

$parentFixtures = [
    [
        'name' => '[Klevu][Product Test 1] Top Level: Enabled',
        'description' => '[Klevu Test Fixtures]',
        'parent_id' => 2,
        'level' => 2,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => true,
        'position' => 1010,
        'url_key' => 'klevu-product-test-1-top-level-enabled',
    ],
    [
        'name' => '[Klevu][Product Test 2] Top Level: Disabled',
        'description' => '[Klevu Test Fixtures]',
        'parent_id' => 2,
        'level' => 2,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => false,
        'position' => 1020,
        'url_key' => 'klevu-product-test-2-top-level-disabled',
    ],
    [
        'name' => '[Klevu][Product Test 3] Nested Parent: Enabled',
        'description' => '[Klevu Test Fixtures]',
        'parent_id' => 2,
        'level' => 2,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => true,
        'position' => 1030,
        'url_key' => 'klevu-product-test-3-nested-parent-enabled',
    ],
    [
        'name' => '[Klevu][Product Test 4] Nested Parent: Disabled',
        'description' => '[Klevu Test Fixtures]',
        'parent_id' => 2,
        'level' => 2,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => false,
        'position' => 1040,
        'url_key' => 'klevu-product-test-4-nested-parent-disabled',
    ],
    [
        'name' => '[Klevu][Product Test 5] Nested Parent: Enabled',
        'description' => '[Klevu Test Fixtures]',
        'parent_id' => 2,
        'level' => 2,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => true,
        'position' => 1050,
        'url_key' => 'klevu-product-test-5-nested-parent-enabled',
    ],
];

foreach ($parentFixtures as $fixture) {
    /** @var Category $category */
    $category = $objectManager->create(Category::class);
    $category->isObjectNew(true);
    $category->addData($fixture);

    $category = $categoryRepository->save($category);
}

/** @var CategoryCollection $parentCollection */
$parentCollection = $objectManager->create(CategoryCollection::class);
$parentCollection->addAttributeToFilter('name', ['like' => '[Klevu][Product Test %']);

/** @var Category $category */
$category = $objectManager->create(Category::class);
$category->isObjectNew(true);
$category->addData([
    'name' => '[Klevu][Product Test 3] Nested Child: Enabled',
    'description' => '[Klevu Test Fixtures]',
    'parent_id' => (int)current(
            $parentCollection->getItemsByColumnValue('name', '[Klevu][Product Test 3] Nested Parent: Enabled')
        )->getId(),
    'level' => 3,
    'available_sort_by' => 'name',
    'default_sort_by' => 'name',
    'is_active' => true,
    'position' => 1031,
    'url_key' => 'klevu-product-test-3-nested-child-enabled',
]);
$categoryRepository->save($category);

/** @var Category $category */
$category = $objectManager->create(Category::class);
$category->isObjectNew(true);
$category->addData([
    'name' => '[Klevu][Product Test 4] Nested Child: Enabled',
    'description' => '[Klevu Test Fixtures]',
    'parent_id' => (int)current(
            $parentCollection->getItemsByColumnValue('name', '[Klevu][Product Test 4] Nested Parent: Disabled')
        )->getId(),
    'level' => 3,
    'available_sort_by' => 'name',
    'default_sort_by' => 'name',
    'is_active' => true,
    'position' => 1041,
    'url_key' => 'klevu-product-test-4-nested-child-enabled',
]);
$categoryRepository->save($category);

/** @var Category $category */
$category = $objectManager->create(Category::class);
$category->isObjectNew(true);
$category->addData([
    'name' => '[Klevu][Product Test 5] Nested Child: Disabled',
    'description' => '[Klevu Test Fixtures]',
    'parent_id' => (int)current(
            $parentCollection->getItemsByColumnValue('name', '[Klevu][Product Test 5] Nested Parent: Enabled')
        )->getId(),
    'level' => 3,
    'available_sort_by' => 'name',
    'default_sort_by' => 'name',
    'is_active' => false,
    'position' => 1051,
    'url_key' => 'klevu-product-test-5-nested-child-disabled',
]);
$categoryRepository->save($category);

/** @var Category $category */
$category = $objectManager->create(Category::class);
$category->isObjectNew(true);
$category->addData([
    'name' => '[Klevu][Product Test 6]: Is Exclude Cat',
    'description' => '[Klevu Test Fixtures]',
    'parent_id' => 2,
    'level' => 2,
    'is_exclude_cat' => true,
    'available_sort_by' => 'name',
    'default_sort_by' => 'name',
    'is_active' => true,
    'position' => 1061,
    'url_key' => 'klevu-product-test-6-is-exclude-cat',
]);
$categoryRepository->save($category);
