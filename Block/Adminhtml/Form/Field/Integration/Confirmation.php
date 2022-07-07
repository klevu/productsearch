<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration;

use Klevu\Search\Block\Adminhtml\Form\Field\Integration\Confirmation\Button;
use Magento\Backend\Block\Template;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class Confirmation extends Fieldset
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
            Button::class,
            'klevu_integration_confirm'
        );
        $block->setTemplate(
            'Klevu_Search::klevu/search/form/field/integration/confirm.phtml'
        );

        return $block->toHtml();
    }
}
