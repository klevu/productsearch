<?php

namespace Klevu\Search\Plugin\Search\ViewModel;

use Klevu\Search\Helper\Config as KlevuSearchHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Search\ViewModel\ConfigProvider;
use Magento\Store\Model\ScopeInterface;

class ConfigProviderPlugin
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param ConfigProvider $subject
     * @param bool $result
     *
     * @return bool
     */
    public function afterIsSuggestionsAllowed(ConfigProvider $subject, $result)
    {
        $klevuEnabled = $this->scopeConfig->isSetFlag(
            KlevuSearchHelper::XML_PATH_EXTENSION_ENABLED,
            ScopeInterface::SCOPE_STORE
        );

        return $klevuEnabled ? false : $result;
    }
}