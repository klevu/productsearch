<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration;

use Klevu\Search\Block\Adminhtml\Form\Field\Integration\Warnings\ObjectVsCollection;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class WarningObjectVsCollection extends Fieldset
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
            ObjectVsCollection::class,
            'klevu_search_information_object_vs_collection_warning'
        );
        $block->setTemplate(
            'Klevu_Search::klevu/search/form/field/integration/warnings/objectvscollection.phtml'
        );

        return $block->toHtml();
    }
}
