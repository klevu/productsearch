<?php

namespace Klevu\Search\Service\Account;

use Klevu\Search\Api\Service\Account\IntegrationStatusInterface;
use Klevu\Search\Api\Service\Account\Model\AccountDetailsInterface;
use Klevu\Search\Api\Service\Account\UpdateEndpointsInterface;
use Klevu\Search\Exception\InvalidApiResponseException;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class UpdateEndpoints implements UpdateEndpointsInterface
{
    const XML_PATH_ANALYTICS_URL = "klevu_search/general/analytics_url";
    const XML_PATH_CATEGORY_NAVIGATION_URL = "klevu_search/general/category_navigation_url";
    const XML_PATH_CATEGORY_NAVIGATION_TRACKING_URL = "klevu_search/general/category_navigation_tracking_url";
    const XML_PATH_INDEXING_URL = "klevu_search/general/rest_hostname";
    const XML_PATH_JS_URL = "klevu_search/general/js_url";
    const XML_PATH_SEARCH_URL_V1 = "klevu_search/general/cloud_search_url";
    const XML_PATH_SEARCH_URL = "klevu_search/general/cloud_search_v2_url";
    const XML_PATH_TIRES_URL = "klevu_search/general/tiers_url";

    /**
     * @var ScopeConfigWriterInterface
     */
    private $scopeConfigWriter;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var IntegrationStatusInterface
     */
    private $integrationStatus;
    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    public function __construct(
        ScopeConfigWriterInterface $scopeConfigWriter,
        StoreManagerInterface $storeManager,
        IntegrationStatusInterface $integrationStatus,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->scopeConfigWriter = $scopeConfigWriter;
        $this->storeManager = $storeManager;
        $this->integrationStatus = $integrationStatus;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * @param AccountDetailsInterface $accountDetails
     * @param string|int $storeId
     *
     * @return void
     * @throws InvalidApiResponseException
     * @throws NoSuchEntityException
     * @throws \Zend_Validate_Exception
     */
    public function execute(AccountDetailsInterface $accountDetails, $storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        $this->saveEndpoints($accountDetails, $store);
        $this->resetLastSyncDate($store);
        $this->integrationStatus->setJustIntegrated($store);
        $this->reinitableConfig->reinit();
    }

    /**
     * @param AccountDetailsInterface $accountDetails
     * @param StoreInterface $store
     *
     * @return void
     */
    private function saveEndpoints(AccountDetailsInterface $accountDetails, StoreInterface $store)
    {
        $endpoints = $this->getEndpointsFromAccountDetails($accountDetails);

        foreach ($endpoints as $configPath => $endpoint) {
            if ($endpoint) {
                $this->scopeConfigWriter->save(
                    $configPath,
                    $endpoint,
                    ScopeInterface::SCOPE_STORES,
                    $store->getId()
                );
                continue;
            }
            $this->scopeConfigWriter->delete(
                $configPath,
                ScopeInterface::SCOPE_STORES,
                $store->getId()
            );
        }
    }

    /**
     * @param AccountDetailsInterface $accountDetails
     *
     * @return array
     */
    private function getEndpointsFromAccountDetails(AccountDetailsInterface $accountDetails)
    {
        return [
            static::XML_PATH_ANALYTICS_URL => $accountDetails->getAnalyticsUrl(),
            static::XML_PATH_CATEGORY_NAVIGATION_URL => $accountDetails->getCatNavUrl(),
            static::XML_PATH_CATEGORY_NAVIGATION_TRACKING_URL => $accountDetails->getCatNavTrackingUrl(),
            static::XML_PATH_INDEXING_URL => $accountDetails->getIndexingUrl(),
            static::XML_PATH_JS_URL => $accountDetails->getJsUrl(),
            static::XML_PATH_SEARCH_URL_V1 => $accountDetails->getSearchUrl(),
            static::XML_PATH_SEARCH_URL => $accountDetails->getSearchUrl(),
            static::XML_PATH_TIRES_URL => $accountDetails->getTiersUrl(),
        ];
    }

    /**
     * @param StoreInterface $store
     *
     * @return void
     */
    private function resetLastSyncDate(StoreInterface $store)
    {
        $this->scopeConfigWriter->save(
            GetFeatures::XML_PATH_FEATURES_LAST_SYNC_DATE,
            0,
            ScopeInterface::SCOPE_STORES,
            $store->getId()
        );
    }
}
