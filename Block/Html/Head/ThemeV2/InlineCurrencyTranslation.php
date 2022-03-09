<?php

namespace Klevu\Search\Block\Html\Head\ThemeV2;

use Klevu\FrontendJs\Block\Template as FrontendJsTemplate;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;

/**
 * @todo Replace with ViewModels when older Magento support is dropped
 */
class InlineCurrencyTranslation extends FrontendJsTemplate
{
    /**
     * @var StoreInterface
     */
    private $currentStore;

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

    /**
     * @return bool
     */
    public function shouldOutputLandingScript()
    {
        return (bool)$this->getData('output_landing_script');
    }

    /**
     * @return bool
     */
    public function shouldOutputQuickScript()
    {
        return (bool)$this->getData('output_quick_script');
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->getBaseCurrencyCode() === $this->getCurrentCurrencyCode()) {
            return '';
        }

        return parent::_toHtml();
    }
}
