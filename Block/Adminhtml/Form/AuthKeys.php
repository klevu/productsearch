<?php

namespace Klevu\Search\Block\Adminhtml\Form;

use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class AuthKeys extends Fieldset
{
    /**
     * @param AbstractElement $element
     *
     * @return string
     * @throws LocalizedException
     */
    public function render(AbstractElement $element)
    {
        $layout = $this->getLayout();
        $block = $layout->createBlock(
            Field\Integration\AuthKeys::class,
            'klevu_integration_auth_keys_view'
        );
        $block->setTemplate(
            'Klevu_Search::klevu/search/form/field/integration/auth_keys.phtml'
        );
        
        return $block->toHtml();
    }
}
