<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Setup\CategorySetup;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var CategorySetup $installer */
$installer = $objectManager->create(CategorySetup::class);

/** @var AttributeRepositoryInterface $eavRepository */
$eavRepository = $objectManager->get(AttributeRepositoryInterface::class);

try {
    $attribute = $eavRepository->get(
        $installer->getEntityTypeId('catalog_product'),
        'klevu_test_attribute'
    );
    $eavRepository->delete($attribute);
} catch (\Exception $ex) {
    //Nothing to remove
}
