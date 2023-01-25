<?php

namespace Klevu\Search\Block\Adminhtml\Sync\Product;

use Klevu\Search\Api\Service\Account\IntegrationStatusInterface;
use Klevu\Search\Api\Service\Sync\Product\GetStoresWithSyncDisabledInterface;
use Magento\Framework\View\Element\Template;

class Message extends Template
{
    /**
     * @var array
     */
    private $stores;
    /**
     * @var GetStoresWithSyncDisabledInterface
     */
    private $getStoresWithSyncDisabled;
    /**
     * @var IntegrationStatusInterface
     */
    private $integrationStatus;

    /**
     * @param Template\Context $context
     * @param GetStoresWithSyncDisabledInterface $getStoresWithSyncDisabled
     * @param IntegrationStatusInterface $integrationStatus
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        GetStoresWithSyncDisabledInterface $getStoresWithSyncDisabled,
        IntegrationStatusInterface $integrationStatus,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->getStoresWithSyncDisabled = $getStoresWithSyncDisabled;
        $this->integrationStatus = $integrationStatus;
    }

    /**
     * @return bool
     */
    public function showStoreLevelWarning()
    {
        return !$this->_request->getParam('store');
    }

    /**
     * @return bool
     */
    public function isStoreIntegrated()
    {
        return $this->integrationStatus->isIntegrated(null);
    }

    /**
     * @return bool
     */
    public function hasSyncDisabledForStore()
    {
        if (null === $this->stores) {
            $this->stores = $this->getStoresWithSyncDisabled->execute();
        }
        $store = $this->_request->getParam('store');

        return $store && array_key_exists($store, $this->stores);
    }
}
