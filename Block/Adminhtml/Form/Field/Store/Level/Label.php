<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Store\Level;

class Label extends \Magento\Config\Block\System\Config\Form\Field
{

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $request = $this->getRequest();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $config = $objectManager->get('Klevu\Search\Helper\Config');
        if ($element->getScope() == "stores") {
            $store_code = $request->getParam("store");
            return $config->getLastProductSyncRun($store_code);
        } else {
            return __("Switch to store scope to set");
        }
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setData('scope', $element->getScope());

        // Remove the inheritance checkbox
        $element
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();

        return parent::render($element);
    }
}
