<?php

namespace Klevu\Search\Block\Html\Head\ThemeV2;

use Klevu\Search\Helper\Data as DataHelper;
use Klevu\Search\Helper\VersionReader;
use Klevu\Search\Service\ThemeV2\IsEnabledCondition;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Api\Data\StoreInterface;

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
     * @param Context $context
     * @param IsEnabledCondition $isEnabledCondition
     * @param DataHelper $dataHelper
     * @param DirectoryList $directoryList
     * @param VersionReader $versionReader
     * @param array $data
     */
    public function __construct(
        Context $context,
        IsEnabledCondition $isEnabledCondition,
        DataHelper $dataHelper,
        DirectoryList $directoryList,
        VersionReader $versionReader,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->isEnabledCondition = $isEnabledCondition;
        $this->dataHelper = $dataHelper;
        $this->directoryList = $directoryList;
        $this->versionReader = $versionReader;
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
     * @return bool
     */
    public function isPubInUse()
    {
        return 'pub' === $this->directoryList->getUrlPath(DirectoryList::PUB);
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
