<?php

namespace Klevu\Search\Block\Adminhtml\Wizard\Configure;

use Klevu\Search\Helper\Config as Klevu_Config;
use Klevu\Search\Model\Api\Action\Getplans as Klevu_Plans;
use Magento\Backend\Block\Template\Context as Template_Context;

class Userplan extends \Magento\Backend\Block\Template
{

    public function __construct(
        Template_Context $context,
        Klevu_Plans $klevuPlans,
        Klevu_Config $klevuConfig,
        array $data = [])
    {
        $this->_klevuPlans = $klevuPlans;
        $this->_klevuConfig = $klevuConfig;
        parent::__construct($context, $data);
    }


    /**
     * Return the submit URL for the user configuration form.
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('klevu_search/wizard/userplan_post');
    }

    /**
     * Return the base URL for the store.
     *
     * @return string
     */
    public function getStoreUrl()
    {
        return $this->getBaseUrl();
    }


    /**
     * Return plans from klevu server.
     *
     * @return array
     */
    public function getPlans()
    {
        $extension_version = $this->_klevuConfig->getModuleInfo();
        $response = $this->_klevuPlans->execute(array("store" => "magento", "extension_version" => (string)$extension_version));
        if ($response->isSuccess()) {
            $plans = $response->getData();
            return $plans['plans']['plan'];
        } else {
            return;
        }
    }


}
