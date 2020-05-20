<?php

namespace Klevu\Search\Block\Adminhtml\Wizard\Configure;

use Magento\Backend\Block\Template\Context as TemplateContext;

class User extends \Magento\Backend\Block\Template
{
    protected $_scopeConfig;

    public function __construct(
        TemplateContext $context,
        array $data = [])
    {
        $this->_scopeConfig = $context->getScopeConfig();
        parent::__construct($context, $data);
    }

    /**
     * Return the submit URL for the user configuration form.
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('klevu_search/wizard/user_post');
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
     * Return the store phone number for the store.
     *
     * @return string
     */
    public function getStorePhoneNumber()
    {
        return $this->_scopeConfig->getValue('general/store_information/phone');
    }
}
