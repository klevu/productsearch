<?php

namespace Klevu\Search\Block\Search\Index;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Service\ThemeV2\IsEnabledCondition;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

class ThemeV2 extends Template
{
    /**
     * @var IsEnabledCondition
     */
    private $isEnabledCondition;

    /**
     * @param Context $context
     * @param IsEnabledCondition $isEnabledCondition
     * @param array $data
     */
    public function __construct(
        Context $context,
        IsEnabledCondition $isEnabledCondition,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->isEnabledCondition = $isEnabledCondition;
    }

    /**
     * @return int|null
     */
    public function getContentMinHeight()
    {
        $configValue = $this->_scopeConfig->getValue(
            ConfigHelper::XML_PATH_SRLP_CONTENT_MIN_HEIGHT,
            ScopeInterface::SCOPE_STORES,
            $this->getStoreId()
        );

        return ((int)$configValue > 0) ? (int)$configValue : null;
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    protected function _toHtml()
    {
        $storeId = $this->getStoreId();
        if (null === $storeId) {
            return '';
        }

        if (!$this->isEnabledCondition->execute($storeId)) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return int|null
     */
    private function getStoreId()
    {
        $storeId = null;
        try {
            $store = $this->_storeManager->getStore();
            $storeId = (int)$store->getId();
        } catch (NoSuchEntityException $e) {
            $this->_logger->error($e->getMessage());
        }

        return $storeId;
    }
}
