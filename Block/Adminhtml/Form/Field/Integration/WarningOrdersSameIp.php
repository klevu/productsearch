<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration;

use Klevu\Search\Block\Adminhtml\Form\Field\Integration\Warnings\OrdersSameIp;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class WarningOrdersSameIp extends Fieldset
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
            OrdersSameIp::class,
            'klevu_search_information_multiple_orders_same_ip'
        );
        $block->setTemplate(
            'Klevu_Search::klevu/search/form/field/integration/warnings/multipleorderssameip.phtml'
        );

        return $block->toHtml();
    }
}
