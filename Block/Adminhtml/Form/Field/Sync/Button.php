<?php

/**
 * Class \Klevu\Search\Block\Adminhtml\Form\Field\Sync\Button
 *
 * @method setStoreId($id)
 * @method string getStoreId()
 */

namespace Klevu\Search\Block\Adminhtml\Form\Field\Sync;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Sync as Klevu_Sync;
use Klevu\Search\Model\System\Config\Source\Syncoptions;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class Button extends Field
{
    /**
     * @var string
     */
    protected $_template = 'klevu/search/form/field/sync/button.phtml';
    /**
     * @var Klevu_Sync
     */
    protected $_klevuSync;

    public function __construct(
        Context $context,
        Klevu_Sync $klevuSync,
        array $data = []
    ) {
        $this->_klevuSync = $klevuSync; // left in place for bc
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if ($element->getScope() === ScopeInterface::SCOPE_STORES) {
            $this->setStoreId($element->getScopeId());
        }

        // Remove the scope information so it doesn't get printed out
        $element->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $storeId = $this->getStoreId();
        $url_params = $storeId ? ["store" => $storeId] : [];

        $this->addData([
            "sync_option_id" => Syncoptions::SYNC_PARTIALLY,
            "html_id" => $element->getHtmlId(),
            "button_label" => __("Sync Catalog Data"),
            "destination_url" => $this->getUrl("klevu_search/sync/all/", $url_params)
        ]);

        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getStoreParam()
    {
        $request = $this->getRequest();

        return $request->getParam('store');
    }

    /**
     * @param $store_id
     *
     * @return string
     */
    public function getRestApi($store_id)
    {
        return $this->_scopeConfig->getValue(
            ConfigHelper::XML_PATH_REST_API_KEY,
            ScopeInterface::SCOPE_STORES,
            (int)$store_id
        );
    }

    /**
     * @return string
     */
    public function getSyncUrlForStore()
    {
        try {
            $storeId = $this->_storeManager->isSingleStoreMode() ?
                $this->_storeManager->getStore()->getId() :
                (int)$this->getStoreParam();
        } catch (NoSuchEntityException $exception) {
            $this->_logger->error(sprintf('Could not load store: %s', $exception->getMessage()));
            return '';
        }

        $restApiHash = hash('sha256', $this->getRestApi($storeId));
        try {
            $store = $this->_storeManager->getStore($storeId);
        } catch (NoSuchEntityException $exception) {
            $this->_logger->error(sprintf('Could not load store: %s', $exception->getMessage()));
            return '';
        }

        return $store->getBaseUrl() . "search/index/syncstore/store/" . $storeId . "/hashkey/" . $restApiHash;
    }

    /**
     * @return string
     */
    public function getSyncOptions()
    {
        return (string)$this->getDataUsingMethod('sync_option_id') ?: '';
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('klevu_search/savesyncoption/option') . 'sync_options=' . $this->getSyncOptions();
    }
}
