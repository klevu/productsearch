<?php

namespace Klevu\Search\Block\Html\Head\ThemeV2;

use Klevu\Search\Api\SerializerInterface;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as DataHelper;
use Klevu\Search\Helper\VersionReader;
use Klevu\Search\Service\ThemeV2\IsEnabledCondition;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Format as LocaleFormat;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

class JsVariables extends Template
{
    /**
     * @var IsEnabledCondition
     */
    private $isEnabledCondition;

    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var VersionReader
     */
    private $versionReader;

    /**
     * @var StoreInterface
     */
    private $currentStore;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var LocaleFormat
     */
    private $localeFormat;

    /**
     * @param Context $context
     * @param IsEnabledCondition $isEnabledCondition
     * @param DataHelper $dataHelper
     * @param DirectoryList $directoryList
     * @param VersionReader $versionReader
     * @param mixed[] $data
     * @param SerializerInterface|null $serializer
     * @param LocaleFormat|null $localeFormat
     */
    public function __construct(
        Context $context,
        IsEnabledCondition $isEnabledCondition,
        DataHelper $dataHelper,
        DirectoryList $directoryList,
        VersionReader $versionReader,
        array $data = [],
        SerializerInterface $serializer = null,
        LocaleFormat $localeFormat = null
    ) {
        parent::__construct($context, $data);

        $this->isEnabledCondition = $isEnabledCondition;
        $this->dataHelper = $dataHelper;
        $this->directoryList = $directoryList;
        $this->versionReader = $versionReader;
        $objectManager = ObjectManager::getInstance();
        $this->serializer = $serializer ?: $objectManager->get(SerializerInterface::class);
        $this->localeFormat = $localeFormat ?: $objectManager->get(LocaleFormat::class);
    }

    /**
     * @return string
     */
    public function getStoreLanguage()
    {
        return $this->dataHelper->getStoreLanguage($this->getCurrentStore());
    }

    /**
     * @return string|null
     */
    public function getBaseCurrencyCode()
    {
        $return = null;
        $store = $this->getCurrentStore();
        if (method_exists($store, 'getBaseCurrencyCode')) {
            $return = $store->getBaseCurrencyCode();
        }

        return $return;
    }

    /**
     * @return string|null
     */
    public function getCurrentCurrencyCode()
    {
        $return = null;
        $store = $this->getCurrentStore();
        if (method_exists($store, 'getCurrentCurrencyCode')) {
            $return = $store->getCurrentCurrencyCode();
        }

        return $return;
    }

    /**
     * @return string
     */
    public function getCurrentCurrencyRates()
    {
        return $this->dataHelper->getCurrencyData($this->getCurrentStore());
    }

    /**
     * @return mixed[]
     */
    public function getKlevuPriceFormatterConfiguration(): array
    {
        $priceFormat = $this->localeFormat->getPriceFormat(null, $this->getCurrentCurrencyCode());

        $currencySymbol = trim(str_replace('%s', '', $priceFormat['pattern']));
        $appendCurrencyAtLast = (0 === strpos($priceFormat['pattern'], '%s'));

        return [
            'appendCurrencyAtLast' => $appendCurrencyAtLast,
            'currencySymbol' => $currencySymbol,
            'decimalPlaces' => $priceFormat['precision'],
            'decimalSeparator' => $priceFormat['decimalSymbol'],
            'thousandSeparator' => $priceFormat['groupSymbol'],
            'grouping' => $priceFormat['groupLength'],
            'format' => str_replace(
                $currencySymbol,
                '%s',
                $priceFormat['pattern'],
            ),
        ];
    }

    /**
     * @return string|null
     */
    public function getKlevuPriceFormatterConfigurationSerialized(): ?string
    {
        $currentStore = $this->getCurrentStore();
        if (!$currentStore) {
            return null;
        }

        $useMagentoCurrencyFormat = $this->_scopeConfig->isSetFlag(
            ConfigHelper::XML_PATH_USE_MAGENTO_CURRENCY_FORMAT,
            ScopeInterface::SCOPE_STORES,
            (int)$currentStore->getId()
        );

        return $useMagentoCurrencyFormat
            ? $this->serializer->serialize(
                $this->getKlevuPriceFormatterConfiguration()
            )
            : null;
    }

    /**
     * @return bool
     */
    public function isPubInUse()
    {
        return 'pub' !== $this->directoryList->getUrlPath(DirectoryList::PUB);
    }

    /**
     * @param string $moduleName
     * @return string
     */
    public function getModuleVersion($moduleName)
    {
        return (string)$this->versionReader->getVersionString($moduleName);
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        $store = $this->getCurrentStore();
        if (!$store || !$this->isEnabledCondition->execute((int)$store->getId())) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return StoreInterface|null
     */
    private function getCurrentStore()
    {
        if (null === $this->currentStore) {
            try {
                $this->currentStore = $this->_storeManager->getStore();
            } catch (NoSuchEntityException $e) {
                $this->_logger->error($e->getMessage());
            }
        }

        return $this->currentStore;
    }
}
