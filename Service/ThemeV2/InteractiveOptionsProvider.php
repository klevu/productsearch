<?php

namespace Klevu\Search\Service\ThemeV2;

use Klevu\FrontendJs\Api\InteractiveOptionsProviderInterface;
use Klevu\FrontendJs\Api\IsEnabledConditionInterface;
use Klevu\Search\Helper\Api as ApiHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\System\Config\Source\Landingoptions;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class InteractiveOptionsProvider implements InteractiveOptionsProviderInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var IsEnabledConditionInterface
     */
    private $isEnabledCondition;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param IsEnabledConditionInterface $isEnabledCondition
     */
    public function __construct(
        ScopeConfigInterface        $scopeConfig,
        IsEnabledConditionInterface $isEnabledCondition
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->isEnabledCondition = $isEnabledCondition;
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function execute($storeId = null)
    {
        if (!$this->isEnabledCondition->execute($storeId)) {
            return [];
        }

        $apiKey = $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_JS_API_KEY,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $cloudSearchUrl = $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_CLOUD_SEARCH_V2_URL,
            ScopeInterface::SCOPE_STORES,
            $storeId
        ) ?: ApiHelper::ENDPOINT_CLOUD_SEARCH_V2_URL;
        $landingUri = $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_LANDING_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $quicksearchSelector = trim((string)$this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_QUICKSEARCH_SELECTOR,
            ScopeInterface::SCOPE_STORES,
            $storeId
        ));
        if ($quicksearchSelector) {
            $quicksearchSelector .= ',';
        }
        $quicksearchSelector .= '.kuSearchInput';

        return [
            'url' => [
                'protocol' => 'https',
                'landing' => (int)$landingUri === Landingoptions::KlEVULAND
                    ? '/search'
                    : '/catalogsearch/result',
                'search' => sprintf('https://%s/cs/v2/search', $cloudSearchUrl),
            ],
            'search' => [
                'minChars' => 0,
                'searchBoxSelector' => $quicksearchSelector,
                'apiKey' => $apiKey,
            ],
            'analytics' => [
                'apiKey' => $apiKey,
            ]
        ];
    }
}

