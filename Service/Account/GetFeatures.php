<?php

namespace Klevu\Search\Service\Account;

use Exception;
use InvalidArgumentException;
use Klevu\Search\Api\SerializerInterface;
use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;
use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterfaceFactory as AccountFeaturesFactory;
use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Exception\InvalidApiKeyException;
use Klevu\Search\Model\Api\Action\Features as FeaturesApi;
use Klevu\Search\Serializer\Json;
use Klevu\Search\Service\Account\Model\AccountFeatures;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validator\ValidatorInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class GetFeatures implements GetFeaturesInterface
{
    const XML_PATH_UPGRADE_FEATURES = "klevu_search/general/upgrade_features";
    const XML_PATH_REST_API_KEY = "klevu_search/general/rest_api_key";
    const XML_PATH_FEATURES_LAST_SYNC_DATE = "klevu_search/features_api/last_sync_date";
    const API_DATA_SYNC_REQUIRED_EVERY_HOURS = 4;
    const FEATURE_CATEGORY_NAVIGATION = 's.enablecategorynavigation';
    const FEATURE_RECOMMENDATIONS = 'allow.personalizedrecommendations';
    const API_ENDPOINT_GET_FEATURE_VALUES = '/uti/getFeatureValues';

    /**
     * @var FeaturesApi
     */
    private $featuresApi;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var AccountFeaturesFactory
     */
    private $accountFeaturesFactory;
    /**
     * @var ScopeConfigWriterInterface
     */
    private $scopeConfigWriter;
    /**
     * @var int
     */
    private $lastSyncDate = 0;
    /**
     * @var array
     */
    private $accountFeatures = [];
    /**
     * @var SerializerInterface|Json
     */
    private $serializer;
    /**
     * @var ValidatorInterface
     */
    private $restApiKeyValidator;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    public function __construct(
        FeaturesApi $featuresApi,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ScopeConfigWriterInterface $scopeConfigWriter,
        AccountFeaturesFactory $accountFeaturesFactory,
        LoggerInterface $logger,
        SerializerInterface $serializer,
        ValidatorInterface $restApiKeyValidator,
        RequestInterface $request,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->featuresApi = $featuresApi;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->scopeConfigWriter = $scopeConfigWriter;
        $this->accountFeaturesFactory = $accountFeaturesFactory;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->restApiKeyValidator = $restApiKeyValidator;
        $this->request = $request;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * @param $store
     *
     * @return AccountFeaturesInterface|null
     */
    public function execute($store = null)
    {
        $accountFeatures = $this->accountFeaturesFactory->create();

        if (null !== $store && !is_scalar($store) && !($store instanceof StoreInterface)) {
            $this->logger->error(
                sprintf(
                    'Store argument must be null, scalar, or instance of %s; %s passed',
                    StoreInterface::class,
                    is_object($store) ? get_class($store) : gettype($store)
                ),
                ['method' => __METHOD__]
            );

            return null;
        }

        if (empty($store)) {
            $store = $this->request->getParam('store');
            if (!$store) {
                 return null;
            }
        }
        try {
            $store = $this->getStore($store);
        } catch (Exception $exception) {
            return null;
        }

        try {
            $restApi = $this->getRestApi($store);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return $accountFeatures;
        }
        if ($this->isApiDataSyncRequired($store)) {
            $this->syncAccountFeaturesApi($restApi, $store);
        }
        $accountFeaturesData = $this->loadAccountFeatures($store);
        $this->setAccountFeaturesDataOnModel($accountFeatures, $accountFeaturesData);

        return $accountFeatures;
    }

    /**
     * @param StoreInterface $store
     *
     * @return bool
     */
    private function isApiDataSyncRequired(StoreInterface $store)
    {
        $features = $this->loadAccountFeatures($store);
        if (!$features) {
            return true;
        }
        $lastSyncDate = $this->lastSyncDate ?: $this->scopeConfig->getValue(
            static::XML_PATH_FEATURES_LAST_SYNC_DATE,
            ScopeInterface::SCOPE_STORES,
            $store->getId()
        );

        return (int)$lastSyncDate < (time() - (60 * 60 * static::API_DATA_SYNC_REQUIRED_EVERY_HOURS));
    }

    /**
     * @param $store
     *
     * @return StoreInterface
     * @throws InvalidArgumentException
     */
    private function getStore($store = null)
    {
        if (($store instanceof StoreInterface) && $store->getId()) {
            return $store;
        }
        try {
            $store = $this->storeManager->getStore($store);
        } catch (NoSuchEntityException $exception) {
            // intentionally left empty
        }
        if (!($store instanceof StoreInterface) || !$store->getId()) {
            throw new InvalidArgumentException('Store could not be found');
        }

        return $store;
    }

    /**
     * @param StoreInterface $store
     *
     * @return string
     * @throws InvalidApiKeyException
     */
    private function getRestApi(StoreInterface $store)
    {
        $restApi = $this->scopeConfig->getValue(
            static::XML_PATH_REST_API_KEY,
            ScopeInterface::SCOPE_STORES,
            $store->getId()
        );
        if (!$this->restApiKeyValidator->isValid($restApi)) {
            throw new InvalidApiKeyException(
                __('Invalid Rest API Key: ' . implode('; ', $this->restApiKeyValidator->getMessages())),
                null,
                400
            );
        }

        return $restApi;
    }

    /**
     * @param string $restApi
     * @param StoreInterface $store
     *
     * @return void
     */
    private function syncAccountFeaturesApi($restApi, StoreInterface $store)
    {
        try {
            $params = [
                "restApiKey" => $restApi,
                "store" => $store->getId()
            ];
            $response = $this->featuresApi->execute($params);
            if ($response->isSuccess()) {
                $responseData = $response->getData();
                $accountFeaturesDataToSave = $this->getFeatureValuesFromRestApi($params, $responseData);
                $this->saveAccountFeatures($store, $accountFeaturesDataToSave);
                $this->setLastSyncDate($store);
                $this->reinitableConfig->reinit();
            } else {
                $this->logger->error(sprintf("Failed to fetch feature details (%s)", $response->getMessage()));
            }
        } catch (Exception $exception) {
            $this->logger->error(sprintf(
                "Klevu Features API call failed: \Klevu\Search\Model\Api\Action\Features::execute - (%s)",
                $exception->getMessage()
            ));
            $this->setLastSyncDate($store, 0);
            $this->reinitableConfig->reinit();
        }
    }

    /**
     * @param array $params
     * @param array $accountFeaturesData
     *
     * @return array
     */
    private function getFeatureValuesFromRestApi(array $params, array $accountFeaturesData)
    {
        try {
            $params['endpoint'] = self::API_ENDPOINT_GET_FEATURE_VALUES;
            $params['features'] = self::FEATURE_CATEGORY_NAVIGATION . ',' . self::FEATURE_RECOMMENDATIONS;

            $response = $this->featuresApi->execute($params);
            $keyMap = [
                self::FEATURE_CATEGORY_NAVIGATION => AccountFeatures::PM_FEATUREFLAG_CATEGORY_NAVIGATION,
                self::FEATURE_RECOMMENDATIONS => AccountFeatures::PM_FEATUREFLAG_RECOMMENDATIONS,
            ];

            foreach ($response->getData('feature') as $feature) {
                $featureKey = (string)$feature['key'];
                if (!isset($keyMap[$featureKey])) {
                    continue;
                }
                if (!isset($feature['value']) || is_array($feature['value'])) {
                    continue;
                }
                switch ((string)$feature['value']) {
                    case 'enabled':
                    case 'yes':
                        $accountFeaturesData['enabled'] .= ',' . $keyMap[$featureKey];
                        break;

                    case 'disabled':
                    case 'no':
                        $accountFeaturesData['disabled'] .= ',' . $keyMap[$featureKey];
                        break;

                    default:
                        break;
                }
            }
        } catch (Exception $exception) {
            $this->logger->error(sprintf(
                "Klevu Features API call failed: \Klevu\Search\Model\Api\Action\Features::execute - (%s)",
                $exception->getMessage()
            ));
        }

        return $accountFeaturesData;
    }

    /**
     * @param StoreInterface $store
     * @param array $accountFeatures
     *
     * @return void
     */
    private function saveAccountFeatures(StoreInterface $store, array $accountFeatures)
    {
        try {
            ksort($accountFeatures);
            $data = $this->serializer->serialize($accountFeatures);
        } catch (Exception $exception) {
            $this->logger->error('Could not serialize Features API response: ' . $exception->getMessage());

            return;
        }
        $savedData = $this->scopeConfig->getValue(
            static::XML_PATH_UPGRADE_FEATURES,
            ScopeInterface::SCOPE_STORES,
            $store->getId()
        );
        if ($data !== $savedData) {
            $this->scopeConfigWriter->save(
                static::XML_PATH_UPGRADE_FEATURES,
                $data,
                ScopeInterface::SCOPE_STORES,
                $store->getId()
            );
            $this->accountFeatures[$store->getId()] = $accountFeatures;
        }
    }

    /**
     * @param StoreInterface $store
     *
     * @return array
     */
    private function loadAccountFeatures(StoreInterface $store)
    {
        if (!isset($this->accountFeatures[$store->getId()])) {
            $savedData = $this->scopeConfig->getValue(
                static::XML_PATH_UPGRADE_FEATURES,
                ScopeInterface::SCOPE_STORES,
                $store->getId()
            );
            try {
                $this->accountFeatures[$store->getId()] = $savedData && trim($savedData) !== '' ?
                    $this->serializer->unserialize($savedData) :
                    [];
            } catch (Exception $exception) {
                $this->logger->error(
                    'Could not load account features from core config data: ' .
                    $exception->getMessage()
                );

                return [];
            }
        }

        return $this->accountFeatures[$store->getId()];
    }

    /**
     * @param StoreInterface $store
     * @param int|null $lastSyncDate
     *
     * @return void
     */
    private function setLastSyncDate(StoreInterface $store, $lastSyncDate = null)
    {
        if (null === $lastSyncDate) {
            $lastSyncDate = time();
        }
        $this->lastSyncDate = $lastSyncDate;

        $this->scopeConfigWriter->save(
            GetFeatures::XML_PATH_FEATURES_LAST_SYNC_DATE,
            $this->lastSyncDate,
            ScopeInterface::SCOPE_STORES,
            $store->getId()
        );
    }

    /**
     * @param AccountFeaturesInterface $accountFeatures
     * @param array $response
     *
     * @return void
     */
    private function setAccountFeaturesDataOnModel(
        AccountFeaturesInterface $accountFeatures,
        array $response
    ) {
        if (isset($response['upgrade_url']) && $response['upgrade_url']) {
            $accountFeatures->setUpgradeUrl((string)$response['upgrade_url']);
        }
        if (isset($response['upgrade_label']) && $response['upgrade_label']) {
            $accountFeatures->setUpgradeLabel((string)$response['upgrade_label']);
        }
        if (isset($response['upgrade_message']) && $response['upgrade_message']) {
            $accountFeatures->setUpgradeMessage((string)$response['upgrade_message']);
        }
        if (isset($response['preserve_layout_message']) && $response['preserve_layout_message']) {
            $accountFeatures->setPreserveLayoutMessage((string)$response['preserve_layout_message']);
        }
        if (isset($response['user_plan_for_store']) && $response['user_plan_for_store']) {
            $accountFeatures->setUserPlanForStore((string)$response['user_plan_for_store']);
        }

        $enabledFeatures = isset($response['enabled']) && $response['enabled']
            ? array_filter(array_map('trim', explode(',', (string)$response['enabled'])))
            : [];
        $accountFeatures->setEnabledFeatures($enabledFeatures);

        $disabledFeatures = isset($response['disabled']) && $response['disabled']
            ? array_filter(array_map('trim', explode(',', (string)$response['disabled'])))
            : [];
        $accountFeatures->setDisabledFeatures($disabledFeatures);
    }
}
