<?php

namespace Klevu\Search\Helper;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Api\Action\Adduser as ApiActionAdduser;
use Klevu\Search\Model\Api\Action\Addwebstore as ApiActionAddwebstore;
use Klevu\Search\Model\Api\Action\Checkuserdetail as ApiActionCheckuserdetail;
use Klevu\Search\Model\Api\Action\Gettimezone as ApiActionGettimezone;
use Klevu\Search\Model\Api\Action\Getuserdetail as ApiActionGetuserdetail;
use Klevu\Search\Model\Session as KlevuSession;
use Magento\Backend\Model\Auth\Session as BackendAuthSession;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

class Api extends AbstractHelper
{
    const ENDPOINT_PROTOCOL = 'https://';
    const ENDPOINT_DEFAULT_HOSTNAME = 'box.klevu.com';
    const ENDPOINT_DEFAULT_API_URL = 'api.ksearchnet.com';
    const ENDPOINT_CLOUD_SEARCH_URL = 'eucs.ksearchnet.com';
    const ENDPOINT_CLOUD_SEARCH_V2_URL = 'eucsv2.klevu.com';
    const ENDPOINT_DEFAULT_ANALYTICS_HOSTNAME = 'stats.klevu.com';

    /**
     * @var BackendAuthSession
     */
    protected $_backendModelSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $_appConfigScopeConfigInterface;

    /**
     * @var ApiActionAdduser
     */
    protected $_apiActionAdduser;

    /**
     * @var ApiActionGetuserdetail
     */
    protected $_apiActionGetuserdetail;

    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;

    /**
     * @var ApiActionAddwebstore
     */
    protected $_apiActionAddwebstore;

    /**
     * @var ApiActionGettimezone
     */
    protected $_apiActionGettimezone;

    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;

    /**
     * @var ApiActionCheckuserdetail
     */
    protected $_apiActionCheckuserdetail;
    /**
     * @var KlevuSession
     */
    protected $_searchModelSession;

    /**
     * @var ProductMetadataInterface
     */
    protected $_ProductMetadataInterface;

    /**
     * @param BackendAuthSession $backendModelSession
     * @param ScopeConfigInterface $appConfigScopeConfigInterface
     * @param ApiActionAdduser $apiActionAdduser
     * @param ApiActionGetuserdetail $apiActionGetuserdetail
     * @param Data $searchHelperData
     * @param ApiActionAddwebstore $apiActionAddwebstore
     * @param ApiActionGettimezone $apiActionGettimezone
     * @param Config $searchHelperConfig
     * @param ApiActionCheckuserdetail $apiActionCheckuserdetail
     * @param ProductMetadataInterface $productMetadataInterface
     * @param KlevuSession $searchModelSession
     */
    public function __construct(
        BackendAuthSession $backendModelSession,
        ScopeConfigInterface $appConfigScopeConfigInterface,
        ApiActionAdduser $apiActionAdduser,
        ApiActionGetuserdetail $apiActionGetuserdetail,
        SearchHelper $searchHelperData,
        ApiActionAddwebstore $apiActionAddwebstore,
        ApiActionGettimezone $apiActionGettimezone,
        ConfigHelper $searchHelperConfig,
        ApiActionCheckuserdetail $apiActionCheckuserdetail,
        ProductMetadataInterface $productMetadataInterface,
        KlevuSession $searchModelSession
    ) {
        $this->_backendModelSession = $backendModelSession;
        $this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
        $this->_apiActionAdduser = $apiActionAdduser;
        $this->_apiActionGetuserdetail = $apiActionGetuserdetail;
        $this->_searchHelperData = $searchHelperData;
        $this->_apiActionAddwebstore = $apiActionAddwebstore;
        $this->_apiActionGettimezone = $apiActionGettimezone;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_apiActionCheckuserdetail = $apiActionCheckuserdetail;
        $this->_ProductMetadataInterface = $productMetadataInterface;
        $this->_searchModelSession = $searchModelSession;
    }

    /**
     * Create a new Klevu user using the API and return the user details.
     *
     * @param string $email
     * @param string $password
     * @param string $userPlan
     * @param bool $partnerAccount
     * @param string $url
     * @param string $merchantEmail
     * @param string $contactNo
     * @param StoreInterface|null $store
     *
     * @return array An array containing the following keys:
     *                 success:     boolean value indicating whether the user was created successfully.
     *                 customer_id: the customer ID for the newly created user (on success only).
     *                 message:     a message to be shown to the user.
     * @throws LocalizedException
     */
    public function createUser(
        $email,
        $password,
        $userPlan,
        $partnerAccount,
        $url,
        $merchantEmail,
        $contactNo,
        $store = null
    ) {
        if ($store instanceof StoreInterface) {
            $this->_apiActionAdduser->setDataUsingMethod('store', $store);
        }

        $user = $this->_backendModelSession;
        if (!empty($user->getUser())) {
            $userEmail = $user->getUser()->getEmail();
        } else {
            $userEmail = "";
        }
        $storePhone = $this->_appConfigScopeConfigInterface->getValue('general/store_information/phone');
        $mage_version = $this->_ProductMetadataInterface->getEdition() . $this->_ProductMetadataInterface->getVersion();
        $response = $this->_apiActionAdduser->execute([
            "email" => $email,
            "password" => $password,
            "userPlan" => urlencode($userPlan),
            "partnerAccount" => $partnerAccount,
            "url" => $url,
            "merchantEmail" => $merchantEmail,
            "contactNo" => $contactNo,
            "shopInfo" => $userEmail . ";" . $storePhone . ";" . $mage_version,
            "bmVersion" => 1,
        ]);

        if ($response->isSuccess()) {
            return [
                "success" => true,
                "customer_id" => $response->getCustomerId(),
                "message" => $response->getMessage(),
            ];
        }

        return [
            "success" => false,
            "message" => $response->getMessage(),
        ];
    }

    /**
     * Retrieve the details for the given Klevu user from the API.
     *
     * @param string $email
     * @param string $password
     * @param StoreInterface|null $store
     *
     * @return array An array containing the following keys:
     *                 success: boolean value indicating whether the operation was successful.
     *                 customer_id: (on success only) The customer ID of the requested user.
     *                 webstores: (on success only) A list of webstores the given user has configured.
     *                 message: (on failure only) Error message to be shown to the user.
     * @throws LocalizedException
     */
    public function getUser($email, $password, $store = null)
    {
        if ($store instanceof StoreInterface) {
            $this->_apiActionGetuserdetail->setDataUsingMethod('store', $store);
        }

        $response = $this->_apiActionGetuserdetail->execute([
            'email' => $email,
            'password' => $password,
        ]);

        if ($response->isSuccess()) {
            $webstores = [];

            // Add each webstore as a \Magento\Framework\DataObject
            $webstores_data = $response->getWebstores();
            if ($webstores_data && isset($webstores_data['webstore'])) {
                $webstores_data = $webstores_data['webstore'];

                if (isset($webstores_data['storeName'])) {
                    // Got a single webstore
                    $webstores_data = [$webstores_data];
                }

                $i = 0;
                foreach ($webstores_data as $webstore_data) {
                    $webstore = [
                        'id' => $i++,
                    ];
                    foreach ($webstore_data as $key => $value) {
                        // Convert field names from camelCase to underscore (code taken from \Magento\Framework\Object)
                        $webstore[strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $key))] = $value;
                    }
                    $webstores[] = new DataObject($webstore);
                }
            }

            return [
                "success" => true,
                "customer_id" => $response->getCustomerId(),
                "webstores" => $webstores,
            ];
        }

        return [
            "success" => false,
            "message" => $response->getMessage(),
        ];
    }

    /**
     * Retrieve the information of already Klevu user registered from the API.
     *
     * @param string $email
     * @param StoreInterface $store
     *
     * @return array An array containing the following keys:
     *                 success: boolean value indicating whether the operation was successful.
     *                 message: (on failure only) Error message to be shown to the user.
     * @throws LocalizedException
     */
    public function checkUserDetail($email, $store = null)
    {
        if ($store instanceof StoreInterface) {
            $this->_apiActionCheckuserdetail->setDataUsingMethod('store', $store);
        }

        $response = $this->_apiActionCheckuserdetail->execute([
            "email" => $email,
        ]);

        if ($response->isSuccess()) {
            return [
                "success" => true,
            ];
        }

        return [
            "success" => false,
            "message" => $response->getMessage(),
        ];
    }

    /**
     * Create a Klevu Webstore using the API for the given Magento store.
     *
     * @param int $customer_id
     * @param StoreInterface $store
     *
     * @return array An array with the following keys:
     *                 success: boolean value indicating whether the operation was successful.
     *                 webstore: (success only) \Magento\Framework\Object containing Webstore information.
     *                 message: message to be displayed to the user.
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createWebstore($customer_id, $store)
    {
        $website = $store->getWebsite();
        $name = sprintf(
            "%s - %s - %s - %s",
            $website ? $website->getName() : '',
            $store->getCode(),
            $store->getName(),
            $store->getBaseUrl(UrlInterface::URL_TYPE_WEB)
        );
        $language = $this->_searchHelperData->getStoreLanguage($store);
        $timezone = $store->getConfig(DirectoryHelper::XML_PATH_DEFAULT_TIMEZONE);
        $country = $store->getConfig(DirectoryHelper::XML_PATH_DEFAULT_COUNTRY);
        $locale = $store->getConfig(DirectoryHelper::XML_PATH_DEFAULT_LOCALE);
        $version = $this->getVersion();
        $jsVersion = $this->_appConfigScopeConfigInterface->getValue(
            Config::XML_PATH_THEME_VERSION,
            ScopeInterface::SCOPE_STORES,
            (int)$store->getId()
        );

        $this->_apiActionAddwebstore->setDataUsingMethod('store', $store);
        $response = $this->_apiActionAddwebstore->execute([
            "customerId" => $customer_id,
            "storeName" => $name,
            "language" => $language,
            "timezone" => $timezone,
            "version" => $version,
            "country" => $country,
            "locale" => $locale,
            "testMode" => false,
            "jsVersion" => $jsVersion,
        ]);

        if ($response->isSuccess()) {
            $webstore = new DataObject([
                "store_name" => $name,
                "js_api_key" => $response->getJsApiKey(),
                "rest_api_key" => $response->getRestApiKey(),
                "test_account_enabled" => false,
                "hosted_on" => $response->getHostedOn(),
                "cloud_search_url" => $response->getCloudSearchUrl(),
                "cloud_search_v2_url" => $response->getData('cloud_search_url_a_p_iv_2'),
                "analytics_url" => $response->getAnalyticsUrl(),
                "js_url" => $response->getJsUrl(),
                "rest_hostname" => $response->getRestUrl(),
                "tires_url" => $response->getTiersUrl(),
            ]);
            $this->_searchModelSession->setCurrentKlevuStoreId($store->getId());
            $this->_searchModelSession->setCurrentKlevuRestApiKlevu($response->getRestApiKey());

            return [
                "success" => true,
                "webstore" => $webstore,
                "message" => $response->getMessage(),
            ];
        }

        return [
            "success" => false,
            "message" => $response->getMessage(),
        ];
    }

    /**
     * @return array|string|null
     * @throws LocalizedException
     */
    public function getTimezoneOptions()
    {
        $response = $this->_apiActionGettimezone->execute();

        if ($response->isSuccess()) {
            $options = [];

            $data = $response->getTimezone();

            if (!is_array($data)) {
                $data = [$data];
            }

            foreach ($data as $timezone) {
                $options[] = [
                    "label" => __($timezone),
                    "value" => $this->escapeHtml($timezone),
                ];
            }

            return $options;
        }

        return $response->getMessage();
    }

    /**
     * Get the module version number from the module config.
     * @return string
     */
    public function getVersion()
    {
        return $this->_searchHelperConfig->getModuleInfo();
    }
}
