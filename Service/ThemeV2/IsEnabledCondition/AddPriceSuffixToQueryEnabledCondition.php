<?php

namespace Klevu\Search\Service\ThemeV2\IsEnabledCondition;

use Klevu\FrontendJs\Api\IsEnabledConditionInterface;
use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class AddPriceSuffixToQueryEnabledCondition implements IsEnabledConditionInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var IsEnabledConditionInterface
     */
    private $baseIsEnabledCondition;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param IsEnabledConditionInterface $baseIsEnabledCondition
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        IsEnabledConditionInterface $baseIsEnabledCondition
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->baseIsEnabledCondition = $baseIsEnabledCondition;
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function execute($storeId = null)
    {
        if (!$this->baseIsEnabledCondition->execute($storeId)) {
            return false;
        }

        return $this->scopeConfig->isSetFlag(
            ConfigHelper::XML_PATH_PRICE_PER_CUSTOMER_GROUP_METHOD,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }
}
