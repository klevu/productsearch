<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Klevu\Search\Helper;


use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Customer\Model\Session as CustomerSession;


/**
 * Catalog data helper
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Taxdata extends \Magento\Framework\App\Helper\AbstractHelper 
{

    /**
     * Currently selected store ID if applicable
     *
     * @var int
     */
    protected $_storeId;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;


    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Template filter factory
     *
     * @var \Magento\Catalog\Model\Template\Filter\Factory
     */
    protected $_templateFilterFactory;

    /**
     * Tax class key factory
     *
     * @var \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory
     */
    protected $_taxClassKeyFactory;

    /**
     * Tax helper
     *
     * @var \Magento\Tax\Model\Config
     */
    protected $_taxConfig;

    /**
     * Quote details factory
     *
     * @var \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory
     */
    protected $_quoteDetailsFactory;

    /**
     * Quote details item factory
     *
     * @var \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory
     */
    protected $_quoteDetailsItemFactory;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * Tax calculation service interface
     *
     * @var \Magento\Tax\Api\TaxCalculationInterface
     */
    protected $_taxCalculationService;

    /**
     * Price currency
     *
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $customerGroupRepository;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory
     */
    protected $addressFactory;

    /**
     * @var \Magento\Customer\Api\Data\RegionInterfaceFactory
     */
    protected $regionFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Session $catalogSession
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param Category $catalogCategory
     * @param Product $catalogProduct
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Catalog\Model\Template\Filter\Factory $templateFilterFactory
     
     * @param \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyFactory
     * @param Config $taxConfig
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsFactory
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculationService
     * @param CustomerSession $customerSession
     * @param PriceCurrencyInterface $priceCurrency
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $regionFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyFactory,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsFactory,
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculationService,
        CustomerSession $customerSession,
        \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $regionFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->_taxClassKeyFactory = $taxClassKeyFactory;
        $this->_taxConfig = $taxConfig;
        $this->_quoteDetailsFactory = $quoteDetailsFactory;
        $this->_quoteDetailsItemFactory = $quoteDetailsItemFactory;
        $this->_taxCalculationService = $taxCalculationService;
        $this->_customerSession = $customerSession;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
        parent::__construct($context);
    }

    /**
     * Set a specified store ID value
     *
     * @param int $store
     * @return $this
     */
    public function setStoreId($store)
    {
        $this->_storeId = $store;
        return $this;
    }



    /**
     * @param array $taxAddress
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    private function convertDefaultTaxAddress(array $taxAddress = null)
    {
        if (empty($taxAddress)) {
            return null;
        }
        /** @var \Magento\Customer\Api\Data\AddressInterface $addressDataObject */
        $addressDataObject = $this->addressFactory->create()
            ->setCountryId($taxAddress['country_id'])
            ->setPostcode($taxAddress['postcode']);

        if (isset($taxAddress['region_id'])) {
            $addressDataObject->setRegion($this->regionFactory->create()->setRegionId($taxAddress['region_id']));
        }
        return $addressDataObject;
    }

    /**
     * Get product price with all tax settings processing
     *
     * @param   \Magento\Catalog\Model\Product $product
     * @param   float $price inputted product price
     * @param   bool $includingTax return price include tax flag
     */
    public function getTaxPrice(
        $product,
        $price,
        $store
    ) {
        if (!$price) {
            return $price;
        }

        $store = $this->_storeManager->getStore($store);
			$shippingAddress = null;
            $shippingAddressDataObject = null;
            if ($shippingAddress === null) {
                $shippingAddressDataObject =
                    $this->convertDefaultTaxAddress($this->_customerSession->getDefaultTaxShippingAddress());
            } elseif ($shippingAddress instanceof \Magento\Customer\Model\Address\AbstractAddress) {
                $shippingAddressDataObject = $shippingAddress->getDataModel();
            }
			$billingAddress = null;
            $billingAddressDataObject = null;
            if ($billingAddress === null) {
                $billingAddressDataObject =
                    $this->convertDefaultTaxAddress($this->_customerSession->getDefaultTaxBillingAddress());
            } elseif ($billingAddress instanceof \Magento\Customer\Model\Address\AbstractAddress) {
                $billingAddressDataObject = $billingAddress->getDataModel();
            }

            $taxClassKey = $this->_taxClassKeyFactory->create();
            $taxClassKey->setType(TaxClassKeyInterface::TYPE_ID)
                ->setValue($product->getTaxClassId());
			$ctc = null;
            if ($ctc === null && $this->_customerSession->getCustomerGroupId() != null) {
                $ctc = $this->customerGroupRepository->getById($this->_customerSession->getCustomerGroupId())
                    ->getTaxClassId();
            }

            $customerTaxClassKey = $this->_taxClassKeyFactory->create();
            $customerTaxClassKey->setType(TaxClassKeyInterface::TYPE_ID)
                ->setValue($ctc);

            $item = $this->_quoteDetailsItemFactory->create();
            $item->setQuantity(1)
                ->setCode($product->getSku())
                ->setShortDescription($product->getShortDescription())
                ->setTaxClassKey($taxClassKey)
                ->setIsTaxIncluded(false)
                ->setType('product')
                ->setUnitPrice($price);
			

            $quoteDetails = $this->_quoteDetailsFactory->create();
            $quoteDetails->setShippingAddress($shippingAddressDataObject)
                ->setBillingAddress($billingAddressDataObject)
                ->setCustomerTaxClassKey($customerTaxClassKey)
                ->setItems([$item])
                ->setCustomerId($this->_customerSession->getCustomerId());

            $storeId = null;
            if ($store) {
                $storeId = $store->getId();
            }
            $taxDetails = $this->_taxCalculationService->calculateTax($quoteDetails, $storeId,false);
            $items = $taxDetails->getItems();
            $taxDetailsItem = array_shift($items);
			$product_price_data['include_tax'] = $taxDetailsItem->getPriceInclTax();
			$product_price_data['exclude_tax'] = $taxDetailsItem->getPrice();
			return $product_price_data;
    }
}
