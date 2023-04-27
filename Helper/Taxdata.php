<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Klevu\Search\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Template\Filter\Factory;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Taxdata extends AbstractHelper
{
    /**
     * Currently selected store ID if applicable
     *
     * @var int
     */
    protected $_storeId;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var StringUtils
     */
    protected $string;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Factory
     */
    protected $_templateFilterFactory;

    /**
     * @var TaxClassKeyInterfaceFactory
     */
    protected $_taxClassKeyFactory;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $_taxConfig;

    /**
     * @var QuoteDetailsInterfaceFactory
     */
    protected $_quoteDetailsFactory;

    /**
     * @var QuoteDetailsItemInterfaceFactory
     */
    protected $_quoteDetailsItemFactory;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var TaxCalculationInterface
     */
    protected $_taxCalculationService;

    /**
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
     * @var GroupRepositoryInterface
     */
    protected $customerGroupRepository;

    /**
     * @var AddressInterfaceFactory
     */
    protected $addressFactory;

    /**
     * @var RegionInterfaceFactory
     */
    protected $regionFactory;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Registry $coreRegistry
     * @param TaxClassKeyInterfaceFactory $taxClassKeyFactory
     * @param TaxConfig $taxConfig
     * @param QuoteDetailsInterfaceFactory $quoteDetailsFactory
     * @param QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory
     * @param TaxCalculationInterface $taxCalculationService
     * @param CustomerSession $customerSession
     * @param GroupRepositoryInterface $customerGroupRepository
     * @param AddressInterfaceFactory $addressFactory
     * @param RegionInterfaceFactory $regionFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Registry $coreRegistry,
        TaxClassKeyInterfaceFactory $taxClassKeyFactory,
        TaxConfig $taxConfig,
        QuoteDetailsInterfaceFactory $quoteDetailsFactory,
        QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory,
        TaxCalculationInterface $taxCalculationService,
        CustomerSession $customerSession,
        GroupRepositoryInterface $customerGroupRepository,
        AddressInterfaceFactory $addressFactory,
        RegionInterfaceFactory $regionFactory
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
     *
     * @return $this
     */
    public function setStoreId($store)
    {
        $this->_storeId = $store;

        return $this;
    }

    /**
     * @param array $taxAddress
     *
     * @return AddressInterface|null
     */
    private function convertDefaultTaxAddress(array $taxAddress = null)
    {
        if (empty($taxAddress)) {
            return null;
        }
        /** @var AddressInterface $addressDataObject */
        $addressDataObject = $this->addressFactory->create()
            ->setCountryId($taxAddress['country_id'])
            ->setPostcode($taxAddress['postcode']);

        if (isset($taxAddress['region_id'])) {
            $addressDataObject->setRegion($this->regionFactory->create()->setRegionId($taxAddress['region_id']));
        }

        return $addressDataObject;
    }

    /**
     * @param ProductInterface $product
     * @param float $price
     * @param StoreInterface|string|int|null $store
     *
     * @return array|float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getTaxPrice(ProductInterface $product, $price, $store)
    {
        if (!$price) {
            return $price;
        }

        $store = $this->_storeManager->getStore($store);
        $shippingAddress = null;
        $shippingAddressDataObject = null;
        if ($shippingAddress === null) {
            $shippingAddressDataObject =
                $this->convertDefaultTaxAddress($this->_customerSession->getDefaultTaxShippingAddress());
        } elseif ($shippingAddress instanceof AbstractAddress) {
            $shippingAddressDataObject = $shippingAddress->getDataModel();
        }
        $billingAddress = null;
        $billingAddressDataObject = null;
        if ($billingAddress === null) {
            $billingAddressDataObject =
                $this->convertDefaultTaxAddress($this->_customerSession->getDefaultTaxBillingAddress());
        } elseif ($billingAddress instanceof AbstractAddress) {
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
        $taxDetails = $this->_taxCalculationService->calculateTax($quoteDetails, $storeId, false);
        $items = $taxDetails->getItems();
        $taxDetailsItem = array_shift($items);
        $product_price_data['include_tax'] = $taxDetailsItem->getPriceInclTax();
        $product_price_data['exclude_tax'] = $taxDetailsItem->getPrice();

        return $product_price_data;
    }

    /**
     * @param ProductInterface $product
     * @param float $price
     * @param StoreInterface|string|int|null $store
     *
     * @return array|float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getTaxPriceForCollection(ProductInterface $product, $price, $store)
    {
        if (!$price) {
            return $price;
        }
        $priceIncludesTax = null;
        if ($priceIncludesTax === null) {
            $priceIncludesTax = $this->_taxConfig->priceIncludesTax($store);
        }

        $store = $this->_storeManager->getStore($store);
        $shippingAddress = null;
        $shippingAddressDataObject = null;
        if ($shippingAddress === null) {
            $shippingAddressDataObject =
                $this->convertDefaultTaxAddress($this->_customerSession->getDefaultTaxShippingAddress());
        } elseif ($shippingAddress instanceof AbstractAddress) {
            $shippingAddressDataObject = $shippingAddress->getDataModel();
        }
        $billingAddress = null;
        $billingAddressDataObject = null;
        if ($billingAddress === null) {
            $billingAddressDataObject =
                $this->convertDefaultTaxAddress($this->_customerSession->getDefaultTaxBillingAddress());
        } elseif ($billingAddress instanceof AbstractAddress) {
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
            ->setIsTaxIncluded($priceIncludesTax)
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
        $taxDetails = $this->_taxCalculationService->calculateTax($quoteDetails, $storeId, false);
        $items = $taxDetails->getItems();
        $taxDetailsItem = array_shift($items);
        $product_price_data['include_tax'] = $taxDetailsItem->getPriceInclTax();
        $product_price_data['exclude_tax'] = $taxDetailsItem->getPrice();

        return $product_price_data;
    }
}
