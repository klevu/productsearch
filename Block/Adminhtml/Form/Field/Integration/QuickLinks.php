<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration;

use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class QuickLinks extends Fieldset
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
            Instructions\QuickLinks::class,
            'klevu_search_information_quick_links'
        );
        $block->setTemplate(
            'Klevu_Search::klevu/search/form/field/integration/quicklinks.phtml'
        );

        return $block->toHtml();
    }
}
