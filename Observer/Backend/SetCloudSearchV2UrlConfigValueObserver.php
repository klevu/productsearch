<?php

namespace Klevu\Search\Observer\Backend;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Source\ThemeVersion;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriterInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class SetCloudSearchV2UrlConfigValueObserver implements ObserverInterface
{
    const DEFAULT_CLOUD_SEARCH_V2_VALUE = 'eucsv2.klevu.com';
    const EVENT_NAME = 'admin_system_config_save';
    const FULL_ACTION_NAME = 'adminhtml_system_config_save';
    const CONFIG_SECTION = 'klevu_search';

    /**
     * @var ConfigWriterInterface
     */
    private $configWriter;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    public function __construct(
        ConfigWriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig = null,
        ReinitableConfigInterface $reinitableConfig = null
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig ?: ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        $this->reinitableConfig = $reinitableConfig ?: ObjectManager::getInstance()->get(ReinitableConfigInterface::class);
    }

    /**
     * @param EventObserver $observer
     *
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $event = $observer->getEvent();
        if (!$event || !$this->isRequestForKlevuSearchSave($event)) {
            return;
        }
        $configData = $event->getData('configData');
        if (ThemeVersion::V2 !== $this->getThemeVersion($configData)) {
            return;
        }
        if (!$storeId = $this->getStoreId($configData)) {
            return;
        }
        if (!$cloudSearchUrl = $this->getV1SearchUrl($storeId)) {
            return;
        }
        $cloudSearchV2Url = $this->getV2SearchUrl($storeId);
        if ($cloudSearchV2Url && static::DEFAULT_CLOUD_SEARCH_V2_VALUE !== $cloudSearchV2Url) {
            return;
        }

        $this->configWriter->save(
            ConfigHelper::XML_PATH_CLOUD_SEARCH_V2_URL,
            $this->getV2SearchUrlFromV1SearchUrl($cloudSearchUrl),
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $this->reinitableConfig->reinit();
    }

    /**
     * @param Event $event
     *
     * @return bool
     */
    private function isRequestForKlevuSearchSave(Event $event)
    {
        $request = $event->getDataUsingMethod('request');

        return ($request instanceof RequestInterface) &&
            self::FULL_ACTION_NAME === $request->getFullActionName() &&
            self::CONFIG_SECTION === $request->getParam('section');
    }

    /**
     * @param array $configData
     *
     * @return string
     */
    private function getThemeVersion(array $configData)
    {
        return isset($configData['groups']['developer']['fields']['theme_version']['value'])
            ? $configData['groups']['developer']['fields']['theme_version']['value']
            : '';
    }

    /**
     * @param $configData
     *
     * @return int|null
     */
    private function getStoreId($configData)
    {
        return isset($configData['store']) ? (int)$configData['store'] : null;
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    private function getV1SearchUrl($storeId)
    {
        return $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_CLOUD_SEARCH_URL,
            ScopeInterface::SCOPE_STORES,
            $storeId
        ) ?: null;
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    private function getV2SearchUrl($storeId)
    {
        return $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_CLOUD_SEARCH_V2_URL,
            ScopeInterface::SCOPE_STORES,
            $storeId
        ) ?: null;
    }

    /**
     * @param string $cloudSearchUrl
     *
     * @return string
     */
    private function getV2SearchUrlFromV1SearchUrl($cloudSearchUrl)
    {
        return preg_replace(
            '/^([^.]+)\.(klevu|ksearchnet)\.com$/',
            '$1v2.$2.com',
            $cloudSearchUrl
        );
    }
}
