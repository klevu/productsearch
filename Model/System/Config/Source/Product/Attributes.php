<?php

namespace Klevu\Search\Model\System\Config\Source\Product;

use Klevu\Search\Api\Provider\Sync\ReservedAttributeCodesProviderInterface;
use Magento\Backend\Model\Session;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class Attributes extends \Magento\Backend\Helper\Data
{
   /**
    * Selected products for mass-update
    *
    * @var ProductCollection
    */
    protected $_products;

    /**
     * Array of same attributes for selected products
     *
     * @var AttributeCollection
     */
    protected $_attributes;

    /**
     * Excluded from batch update attribute codes
     *
     * @var string[]
     */
    protected $_excludedAttributes = ['url_key'];

    /**
     * @var ProductCollectionFactory
     */
    protected $_productsFactory;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var EavConfig
     */
    protected $_eavConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param EavConfig $eavConfig
     * @param ReservedAttributeCodesProviderInterface|null $reservedAttributeCodesProvider
     */
    public function __construct(
        EavConfig $eavConfig,
        ReservedAttributeCodesProviderInterface $reservedAttributeCodesProvider = null
    ) {
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
        if ($this->_attributes === null) {
            $this->_attributes = $this->_eavConfig->getEntityType(
                \Magento\Catalog\Model\Product::ENTITY
            )->getAttributeCollection();

            if ($this->_excludedAttributes) {
                $this->_attributes->addFieldToFilter('attribute_code', ['nin' => $this->_excludedAttributes]);
            }

            foreach ($this->_attributes as $attribute) {
                $options[] =
                [
                    'value' => $attribute->getAttributeCode(),
                    'label' => $attribute->getAttributeCode()
                ];
            }
        }

        return $options;
    }
}
