<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration\NextSteps;

use Magento\Backend\Block\Template;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class Introduction extends Fieldset
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
            'klevu_integration_next_steps_introduction'
        );
        $block->setTemplate(
            'Klevu_Search::klevu/search/form/field/integration/next_steps/introduction.phtml'
        );

        return $block->toHtml();
    }
}
