<?php

namespace Klevu\Search\Model\System\Config\Source\Product;

use Klevu\Search\Api\Provider\Eav\Attribute\ProductAttributeCollectionProviderInterface;
use Klevu\Search\Api\Provider\Sync\ReservedAttributeCodesProviderInterface;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Backend\Model\Session;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as EntityAttributeCollection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * class should not extend BackendHelper.
 * remains in place for backward compatibility
 */
class Attributes extends BackendHelper
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
     * @deprecared  is never used, protected so can not be removed
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
     * @var AttributeCollection
     */
    protected $_attributes;
    /**
     * Excluded from batch update attribute codes
     *
     * @var string[]
     */
    protected $_excludedAttributes = [
        'url_key',
    ];
    /**
     * @var ProductAttributeCollectionProviderInterface
     */
    private $productAttributeCollectionProvider;

    /**
     * @param EavConfig $eavConfig
     * @param ReservedAttributeCodesProviderInterface|null $reservedAttributeCodesProvider
     * @param ProductAttributeCollectionProviderInterface|null $productAttributeCollectionProvider
     */
    public function __construct(
        EavConfig $eavConfig, // never used, left in place for backward compatibility
        ReservedAttributeCodesProviderInterface $reservedAttributeCodesProvider = null,
        ProductAttributeCollectionProviderInterface $productAttributeCollectionProvider = null
    ) {
        // __construct should call parent, however we are extending the wrong class.
        // Not calling parent construct is fine in this case.
        $this->_eavConfig = $eavConfig;
        // We don't use OM here as there is no explicit preference for the ReservedAttributeCodesProviderInterface
        //  The implementation depends on context and is added through di.xml
        if (null !== $reservedAttributeCodesProvider) {
            $this->_excludedAttributes = array_filter(
                array_unique(
                    array_merge(
                        $this->_excludedAttributes,
                        $reservedAttributeCodesProvider->execute()
                    )
                )
            );
        }
        $objectManager = ObjectManager::getInstance();
        $this->productAttributeCollectionProvider = $productAttributeCollectionProvider
            ?: $objectManager->create(ProductAttributeCollectionProviderInterface::class);
    }

    /**
     * Return collection of same attributes for selected products without unique
     *
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray()
    {
        $options = [];
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
