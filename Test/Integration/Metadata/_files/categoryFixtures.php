<?php
/** @noinspection PhpDeprecationInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

$entityIdReplacements = [];
$fixtures = [
    '_3' => [
        'name' => '[Klevu] Parent Category 1',
        'description' => '[Klevu Test Fixtures] Parent category 1',
        'parent_id' => 2,
        'path' => '1/2/_3',
        'level' => 2,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => true,
        'position' => 1,
        'url_key' => 'klevu-test-category-1',
    ],
    '_4' => [
        'name' => '[Klevu] Child Category 1-1',
        'description' => '[Klevu Test Fixtures] Child category 1-1',
        'parent_id' => '_3',
        'path' => '1/2/_3/_4',
        'level' => 3,
        'available_sort_by' => 'name',
        'default_sort_by' => 'name',
        'is_active' => true,
        'position' => 1,
        'url_key' => 'klevu-test-category-1-1',
    ]
];

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$collection = $objectManager->get(CategoryCollection::class);
$collection->addAttributeToFilter('url_key', ['in' => array_column($fixtures, 'url_key')]);
$collection->load();
$collection->delete();

/** @var CategoryRepositoryInterface $categoryRepository */
$categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);

foreach ($fixtures as $pseudoEntityId => $fixture) {
    $path = $fixture['path'];
    $parentId = $fixture['parent_id'];
    unset($fixture['path'], $fixture['parent_id']);

    /** @var Category $category */
    $category = $objectManager->create(Category::class);
    $category->isObjectNew(true);
    $category->addData($fixture);
    $category->setParentId(2);
    $category = $categoryRepository->save($category);

    $entityIdReplacements[$pseudoEntityId] = $category->getId();

    if (isset($entityIdReplacements[$parentId])) {
        $category->move(
            $entityIdReplacements[$parentId],
            $fixture['position']
        );
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
