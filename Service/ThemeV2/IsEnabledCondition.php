<?php

namespace Klevu\Search\Service\ThemeV2;

use Klevu\FrontendJs\Api\IsEnabledConditionInterface as FrontendJsIsEnabledConditionInterface;
use Klevu\Metadata\Api\IsEnabledConditionInterface as MetadataIsEnabledConditionInterface;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Source\ThemeVersion;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class IsEnabledCondition implements
    FrontendJsIsEnabledConditionInterface,
    MetadataIsEnabledConditionInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function execute($storeId = null)
    {
        $isEnabled = $this->scopeConfig->isSetFlag(
            ConfigHelper::XML_PATH_EXTENSION_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $apiKey = trim((string)$this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_JS_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        $themeVersion = $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_THEME_VERSION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $isEnabled && $apiKey && ($themeVersion === ThemeVersion::V2);
    }
}
