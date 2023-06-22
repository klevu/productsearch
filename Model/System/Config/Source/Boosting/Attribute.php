<?php

namespace Klevu\Search\Model\System\Config\Source\Boosting;

use Klevu\Search\Api\Provider\Eav\Attribute\ProductAttributeCollectionProviderInterface;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Backend\Model\Session;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as EntityAttributeCollection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;

/**
 * class should not extend BackendHelper.
 * remains in place for backward compatibility
 */
class Attribute extends BackendHelper
{
    /**
     * @deprecated is never used, protected so can not be removed
     * @see // no replacement
     * @var ProductCollection
     */
    protected $_products;
    /**
     * @deprecated is never used, protected so can not be removed
     * @see // no replacement
     * @var ProductCollectionFactory
     */
    protected $_productsFactory;
    /**
     * @deprecated is never used, protected so can not be removed
     * @see // no replacement
     * @var Session
     */
    protected $_session;
    /**
     * @deprecated is never used, protected so can not be removed
     * @see // no replacement
     * @var EavConfig
     */
    protected $_eavConfig;
    /**
     * @deprecated is never used, protected so can not be removed
     * @see // no replacement
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var EntityAttributeCollection
     */
    protected $_attributes;
    /**
     * @var string[]
     */
    protected $_excludedAttributes = ['url_key'];
    /**
     * @var ProductAttributeCollectionProviderInterface
     */
    private $productAttributeCollectionProvider;

    /**
     * @param EavConfig $eavConfig
     * @param ProductAttributeCollectionProviderInterface|null $productAttributeCollectionProvider
     */
    public function __construct(
        EavConfig $eavConfig, // never used, left in place for backward compatibility
        ProductAttributeCollectionProviderInterface $productAttributeCollectionProvider = null
    ) {
        // __construct should call parent, however we are extending the wrong class.
        // Not calling parent construct is fine in this case.
        $this->_eavConfig = $eavConfig;
        $objectManager = ObjectManager::getInstance();
        $this->productAttributeCollectionProvider = $productAttributeCollectionProvider
            ?: $objectManager->create(ProductAttributeCollectionProviderInterface::class);
    }

    /**
     * @return string[][]
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => null,
                'label' => '--- No Attribute Selected ---',
            ],
        ];
        $attributeCollection = $this->getAttributeCollection();
        foreach ($attributeCollection->getItems() as $attribute) {
            /** @var AttributeInterface $attribute */
            $frontendLabel = $attribute->getDefaultFrontendLabel();
            $appendLabel = $frontendLabel
                ? ' - ' . $frontendLabel
                : '';
            $options[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getAttributeCode() . $appendLabel,
            ];
        }

        return $options;
    }

    /**
     * @return EntityAttributeCollection
     */
    private function getAttributeCollection()
    {
        if ($this->_attributes === null) {
            $attributeCollection = $this->productAttributeCollectionProvider->getCollection();
            if ($this->_excludedAttributes) {
                $attributeCollection->addFieldToFilter('attribute_code', ['nin' => $this->_excludedAttributes]);
            }
            $attributeCollection->setOrder('attribute_code', Select::SQL_ASC);

            $this->_attributes = $attributeCollection;
        }

        return $this->_attributes;
    }
}
