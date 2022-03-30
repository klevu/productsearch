<?php
/**
 * Class \Klevu\Search\Block\Adminhtml\Form\Field\Sync\UpdatesOnly
 */
namespace Klevu\Search\Block\Adminhtml\Form\Field\Sync;

use Klevu\Search\Model\Sync as Klevu_Sync;

/**
 * Class UpdatesOnly
 * @package Klevu\Search\Block\Adminhtml\Form\Field\Sync
 */
class UpdatesOnly extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * UpdatesOnly constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param Klevu_Sync $klevuSync
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Klevu_Sync $klevuSync,
        array $data = [])
    {
        $this->_storeManager = $context->getStoreManager();
        $this->_klevuSync = $klevuSync;
        parent::__construct($context, $data);
    }

    protected $_template = 'klevu/search/form/field/sync/updatesonly.phtml';

    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($element->getScope() == "stores") {
            $this->setStoreId($element->getScopeId());
        }

        // Remove the scope information so it doesn't get printed out
        $element
            ->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Retrieve element HTML markup
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $url_params = ($this->getStoreId()) ? ["store" => $this->getStoreId()] : [];
        $label_suffix = ($this->getStoreId()) ? " for This Store" : "";

        $this->addData([
            "sync_option_id" => \Klevu\Search\Model\System\Config\Source\Syncoptions::SYNC_PARTIALLY,
            "html_id" => $element->getHtmlId(),
            "button_label" => sprintf("Sync Updates Only %s", $label_suffix),
            "destination_url" => $this->getUrl("klevu_search/sync/all/store/" . $this->getStoreId(), $url_params)

        ]);

        return $this->_toHtml();
    }

    /**
     * Retrieve store param
     * @return mixed
     */
    public function getStoreParam()
    {
        $store_id = $this->getRequest()->getParam('store');
        return $store_id;
    }

    /**
     * Retrieve Rest API for Magento configured store
     *
     * @param $store_id
     * @return string
     */
    public function getRestApi($store_id)
    {
        return $this->_klevuSync->getHelper()->getConfigHelper()->getRestApiKey((int)$store_id);
    }

    /**
     * Retrieve sync url for current store
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSyncUrlForStore()
    {
        $store = $this->_storeManager;
        if ($store->isSingleStoreMode()) {
            $store_id = $store->getStore()->getId();
        } else {
            $store_id = $this->getRequest()->getParam('store');
        }
        $hashkey = hash('sha256', (string)$this->getRestApi($store_id));
        return $this->_storeManager->getStore($store_id)->getBaseUrl() . "search/index/syncstore/store/" . $store_id . "/hashkey/" . $hashkey;
    }
}

