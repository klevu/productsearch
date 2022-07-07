<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration;

use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;

class Messages extends Fieldset
{
    /**
     * @param AbstractElement $element
     *
     * @return string
     * @throws LocalizedException
     */
    public function render(AbstractElement $element)
    {
        if (!$this->_request->getParam('store')) {
            return '';
        }

        $layout = $this->getLayout();
        $block = $layout->createBlock(
            Template::class,
            'klevu_integration_messages'
        );
        $block->setTemplate(
            'Klevu_Search::klevu/search/form/field/integration/messages.phtml'
        );

        return $block->toHtml();
    }
}
