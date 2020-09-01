<?php

namespace Klevu\Search\Helper;

class Api extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $_backendModelSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_appConfigScopeConfigInterface;

    /**
     * @var \Klevu\Search\Model\Api\Action\Adduser
     */
    protected $_apiActionAdduser;

    /**
     * @var \Klevu\Search\Model\Api\Action\Getuserdetail
     */
    protected $_apiActionGetuserdetail;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * @var \Klevu\Search\Model\Api\Action\Addwebstore
     */
    protected $_apiActionAddwebstore;

    /**
     * @var \Klevu\Search\Model\Api\Action\Gettimezone
     */
    protected $_apiActionGettimezone;

    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;
    
    /**
     * @var \Klevu\Search\Model\Api\Action\Checkuserdetail
     */
    protected $_apiActionCheckuserdetail;
	
	/**
     * @var \Klevu\Search\Model\Session
     */
    protected $_searchModelSession;

    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendModelSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $appConfigScopeConfigInterface,
        \Klevu\Search\Model\Api\Action\Adduser $apiActionAdduser,
        \Klevu\Search\Model\Api\Action\Getuserdetail $apiActionGetuserdetail,
        \Klevu\Search\Helper\Data $searchHelperData,
        \Klevu\Search\Model\Api\Action\Addwebstore $apiActionAddwebstore,
        \Klevu\Search\Model\Api\Action\Gettimezone $apiActionGettimezone,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        \Klevu\Search\Model\Api\Action\Checkuserdetail $apiActionCheckuserdetail,
        \Magento\Framework\App\ProductMetadataInterface $productMetadataInterface,
		\Klevu\Search\Model\Session $searchModelSession
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

    const ENDPOINT_PROTOCOL = 'https://';
    const ENDPOINT_DEFAULT_HOSTNAME = 'box.klevu.com';
	const ENDPOINT_DEFAULT_ANALYTICS_HOSTNAME = 'stats.klevu.com';
	
    /**
     * Create a new Klevu user using the API and return the user details.
     *
     * @param $email
     * @param $password
     * @param $url
     *
     * @return array An array containing the following keys:
     *                 success:     boolean value indicating whether the user was created successfully.
     *                 customer_id: the customer ID for the newly created user (on success only).
     *                 message:     a message to be shown to the user.
     */
    public function createUser($email, $password, $userPlan, $partnerAccount, $url, $merchantEmail, $contactNo)
    {
        $user = $this->_backendModelSession;
		if(!empty($user->getUser())) {
			$userEmail = $user->getUser()->getEmail();
		} else {
			$userEmail = "";
		}
        $storePhone = $this->_appConfigScopeConfigInterface->getValue('general/store_information/phone');
        $mage_version = $this->_ProductMetadataInterface->getEdition().$this->_ProductMetadataInterface->getVersion();
        $response = $this->_apiActionAdduser->execute([
            "email"    => $email,
            "password" => $password,
            "userPlan" => urlencode($userPlan),
            "partnerAccount" => $partnerAccount,
            "url"      => $url,
            "merchantEmail" => $merchantEmail,
            "contactNo" => $contactNo,
            "shopInfo" => $userEmail.";".$storePhone.";".$mage_version,
            "bmVersion" => 1,
        ]);

        if ($response->isSuccess()) {
            return [
                "success"     => true,
                "customer_id" => $response->getCustomerId(),
                "message"     => $response->getMessage()
            ];
        } else {
            return [
                "success" => false,
                "message" => $response->getMessage()
            ];
        }
    }

    /**
     * Retrieve the details for the given Klevu user from the API.
     *
     * @param $email
     * @param $password
     *
     * @return array An array containing the following keys:
     *                 success: boolean value indicating whether the operation was successful.
     *                 customer_id: (on success only) The customer ID of the requested user.
     *                 webstores: (on success only) A list of webstores the given user has configured.
     *                 message: (on failure only) Error message to be shown to the user.
     */
    public function getUser($email, $password)
    {

        $response = $this->_apiActionGetuserdetail->execute([
            "email"    => $email,
            "password" => $password
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
                        'id' => $i++
                    ];
                    foreach ($webstore_data as $key => $value) {
                        // Convert field names from camelCase to underscore (code taken from \Magento\Framework\Object)
                        $webstore[strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $key))] = $value;
                    }
                    $webstores[] = new \Magento\Framework\DataObject($webstore);
                }
            }

            return [
                "success"     => true,
                "customer_id" => $response->getCustomerId(),
                "webstores"   => $webstores
            ];
        } else {
            return [
                "success" => false,
                "message" => $response->getMessage()
            ];
        }
    }
    
    /**
     * Retrieve the information of already Klevu user registered from the API.
     *
     * @param $email
     *
     * @return array An array containing the following keys:
     *                 success: boolean value indicating whether the operation was successful.
     *                 message: (on failure only) Error message to be shown to the user.
     */
    public function checkUserDetail($email)
    {
        $response = $this->_apiActionCheckuserdetail->execute([
            "email"    => $email,
        ]);

        if ($response->isSuccess()) {
            return [
                "success"     => true,
            ];
        } else {
            return [
                "success" => false,
                "message" => $response->getMessage()
            ];
        }
    }

    /**
     * Create a Klevu Webstore using the API for the given Magento store.
     *
     * @param                       $customer_id
     * @param \Magento\Framework\Model\Store $store
     * @param bool                  $test_mode
     *
     * @return array An array with the following keys:
     *                 success: boolean value indicating whether the operation was successful.
     *                 webstore: (success only) \Magento\Framework\Object containing Webstore information.
     *                 message: message to be displayed to the user.
     */
    public function createWebstore($customer_id, $store)
    {
        $name = sprintf(
            "%s - %s - %s - %s",
            $store->getWebsite()->getName(),
            $store->getCode(),
            $store->getName(),
            $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB)
        );
        $language = $this->_searchHelperData->getStoreLanguage($store);
        $timezone = $store->getConfig(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_TIMEZONE);
        $country =  $store->getConfig(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_COUNTRY);
        $locale =   $store->getConfig(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE);

        $version = $this->getVersion();


        $response = $this->_apiActionAddwebstore->execute([
            "customerId" => $customer_id,
            "storeName"  => $name,
            "language"   => $language,
            "timezone"   => $timezone,
            "version"    => $version,
            "country"    => $country,
            "locale"     => $locale,
            "testMode"   => false,
        ]);

        if ($response->isSuccess()) {
            $webstore = new \Magento\Framework\DataObject([
                "store_name"           => $name,
                "js_api_key"           => $response->getJsApiKey(),
                "rest_api_key"         => $response->getRestApiKey(),
                "test_account_enabled" => false,
                "hosted_on"            => $response->getHostedOn(),
                "cloud_search_url"     => $response->getCloudSearchUrl(),
                "analytics_url"        => $response->getAnalyticsUrl(),
                "js_url"               => $response->getJsUrl(),
                "rest_hostname"        => $response->getRestUrl(),
                "tires_url"            => $response->getTiersUrl(),

            ]);
			$this->_searchModelSession->setCurrentKlevuStoreId($store->getId());
			$this->_searchModelSession->setCurrentKlevuRestApiKlevu($response->getRestApiKey());
            return [
                "success"  => true,
                "webstore" => $webstore,
                "message"  => $response->getMessage()
            ];
        } else {
            return [
                "success" => false,
                "message" => $response->getMessage()
            ];
        }
    }

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
                    "value" => $this->escapeHtml($timezone)
                ];
            }

            return $options;
        } else {
            return $response->getMessage();
        }
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
