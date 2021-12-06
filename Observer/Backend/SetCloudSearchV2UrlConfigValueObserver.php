<?php

namespace Klevu\Search\Observer\Backend;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Source\ThemeVersion;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class SetCloudSearchV2UrlConfigValueObserver implements ObserverInterface
{
    const DEFAULT_CLOUD_SEARCH_V2_VALUE = 'eucsv2.klevu.com';

    /**
     * @var ConfigWriterInterface
     */
    private $configWriter;

    /**
     * @param ConfigWriterInterface $configWriter
     */
    public function __construct(
        ConfigWriterInterface $configWriter
    ) {
        $this->configWriter = $configWriter;
    }

    public function execute(EventObserver $observer)
    {
        $event = $observer->getEvent();
        if (!$event) {
            return;
        }

        $request = $event->getDataUsingMethod('request');
        if (!($request instanceof RequestInterface)
            || 'adminhtml_system_config_save' !== $request->getFullActionName()
            || 'klevu_search' !== $request->getParam('section')) {
            return;
        }

        $configData = $event->getData('configData');
        $storeId = isset($configData['store']) ? (int)$configData['store'] : null;

        $themeVersion = isset($configData['groups']['developer']['fields']['theme_version']['value'])
            ? $configData['groups']['developer']['fields']['theme_version']['value']
            : '';
        $cloudSearchUrl = isset($configData['groups']['general']['fields']['cloud_search_url']['value'])
            ? $configData['groups']['general']['fields']['cloud_search_url']['value']
            : null;
        $cloudSearchV2Url = isset($configData['groups']['general']['fields']['cloud_search_v2_url']['value'])
            ? $configData['groups']['general']['fields']['cloud_search_v2_url']['value']
            : null;

        if ($storeId
            && ThemeVersion::V2 === $themeVersion
            && $cloudSearchUrl
            && (!$cloudSearchV2Url || static::DEFAULT_CLOUD_SEARCH_V2_VALUE === $cloudSearchV2Url)) {
            $this->configWriter->save(
                ConfigHelper::XML_PATH_CLOUD_SEARCH_V2_URL,
                preg_replace(
                    '/^([^.]+)\.(klevu|ksearchnet)\.com$/',
                    '$1v2.$2.com',
                    $cloudSearchUrl
                ),
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
        }
    }
}
