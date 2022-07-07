<?php

namespace Klevu\Search\Observer\Backend;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Service\Account\GetFeatures;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriterInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class RestApiKeyChanged implements ObserverInterface
{
    private $resetSyncDateOnFieldChange = [
        ConfigHelper::XML_PATH_REST_API_KEY
    ];
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigWriterInterface
     */
    private $scopeConfigWriter;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigWriterInterface $scopeConfigWriter,
        LoggerInterface $logger,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfigWriter = $scopeConfigWriter;
        $this->logger = $logger;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * @param EventObserver $observer
     *
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        if (!array_intersect($this->resetSyncDateOnFieldChange, $observer->getData('changed_paths'))) {
            return;
        }
        try {
            $store = $this->storeManager->getStore($observer->getData('store'));
        } catch (NoSuchEntityException $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }
        $this->scopeConfigWriter->save(
            GetFeatures::XML_PATH_FEATURES_LAST_SYNC_DATE,
            0,
            ScopeInterface::SCOPE_STORES,
            $store->getId()
        );
        $this->reinitableConfig->reinit();
    }
}
