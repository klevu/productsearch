<?php

namespace Klevu\Search\Block\Adminhtml\Form;

use Magento\Backend\Block\Template;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class Integration extends Fieldset
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
            Template::class,
            'klevu_search_integration_instructions'
        );
        $block->setTemplate(
            'Klevu_Search::klevu/search/form/integration.phtml'
        );

        return $block->toHtml();
    }
}
