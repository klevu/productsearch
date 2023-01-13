<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Sync;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Sync as Klevu_Sync;
use Klevu\Search\Model\System\Config\Source\Syncoptions;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class UpdatesOnly extends Field
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var Klevu_Sync
     */
    protected $_klevuSync;
    /**
     * @var string
     */
    protected $_template = 'klevu/search/form/field/sync/updatesonly.phtml';

    /**
     * UpdatesOnly constructor.
     *
     * @param Context $context
     * @param Klevu_Sync $klevuSync
     * @param array $data
     */
    public function __construct(
        Context $context,
        Klevu_Sync $klevuSync,
        array $data = []
    ) {
        $this->_storeManager = $context->getStoreManager();
        $this->_klevuSync = $klevuSync;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if ($element->getScope() === "stores") {
            $this->setStoreId($element->getScopeId());
        }
        // Remove the scope information so it doesn't get printed out
        $element->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $urlParams = ($this->getStoreId()) ? ["store" => $this->getStoreId()] : [];
        $labelSuffix = ($this->getStoreId()) ? " for This Store" : "";

        $this->addData([
            "sync_option_id" => Syncoptions::SYNC_PARTIALLY,
            "html_id" => $element->getHtmlId(),
            "button_label" => sprintf("Sync Updates Only %s", $labelSuffix),
            "destination_url" => $this->getUrl("klevu_search/sync/all/store/" . $this->getStoreId(), $urlParams)
        ]);

        return $this->_toHtml();
    }

    /**
     * Retrieve store param
     * @return mixed
     */
    public function getStoreParam()
    {
        return $this->getRequest()->getParam('store');
    }

    /**
     * Retrieve Rest API for Magento configured store
     *
     * @param string|int $storeId
     *
     * @return string
     */
    public function getRestApi($storeId)
    {
        $helperManager = $this->_klevuSync->getHelper();
        /** @var ConfigHelper $configHeelpr */
        $configHelper = $helperManager->getConfigHelper();

        return $configHelper->getRestApiKey((int)$storeId);
    }

    /**
     * Retrieve sync url for current store
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSyncUrlForStore()
    {
        $store = $this->_storeManager;
        if ($store->isSingleStoreMode()) {
            $store_id = $store->getStore()->getId();
        } else {
            $store_id = $this->getRequest()->getParam('store');
        }
        $restApiKey = $this->getRestApi($store_id);
        $hashkey = $restApiKey ? hash('sha256', (string)$restApiKey) : '';

        return $this->_storeManager->getStore($store_id)->getBaseUrl()
            . "search/index/syncstore/store/" . $store_id
            . "/hashkey/" . $hashkey;
    }
}
