<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration;

use Klevu\Search\Block\Adminhtml\Form\Field\Integration\Instructions\Integration;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class HowToIntegrate extends Fieldset
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
            Integration::class,
            'klevu_search_information_how_to_integrate'
        );
        $block->setTemplate(
            'Klevu_Search::klevu/search/form/field/integration/howtointegrate.phtml'
        );

        return $block->toHtml();
    }
}
