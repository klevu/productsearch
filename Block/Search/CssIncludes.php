<?php

namespace Klevu\Search\Block\Search;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Source\ThemeVersion;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

class CssIncludes extends Template
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->configHelper = $configHelper;
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    protected function _toHtml()
    {
        try {
            $store = $this->_storeManager->getStore();
            $storeId = (int)$store->getId();
        } catch (NoSuchEntityException $e) {
            $this->_logger->error($e->getMessage());

            return '';
        }

        $themeVersion = $this->_scopeConfig->getValue(
            ConfigHelper::XML_PATH_THEME_VERSION,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        if ((ThemeVersion::V2 === $themeVersion)
            || !$this->configHelper->isExtensionConfigured($storeId)) {
            return '';
        }

        return parent::_toHtml();
    }
}
