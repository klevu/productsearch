<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration;

use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class Versions extends Fieldset
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
            Instructions\Versions::class,
            'klevu_search_information_versions'
        );
        $block->setTemplate(
            'Klevu_Search::klevu/search/form/field/integration/versions.phtml'
        );

        return $block->toHtml();
    }
}
