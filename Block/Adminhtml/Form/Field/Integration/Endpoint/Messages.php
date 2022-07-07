<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration\Endpoint;

use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Messages extends Fieldset
{
    public function render(AbstractElement $element)
    {
        $html = '';
        if (!$this->_request->getParam('store')) {
            return $html;
        }
        $html .= '<tr><td colspan="3"><div>';
        $html .= '<div class="message message-success" id="klevu-integration-endpoint-success-msg" style="display:none;"></div>';
        $html .= '<div class="message message-error" id="klevu-integration-endpoint-error-msg" style="display:none;"></div>';
        $html .= '</div></td></tr>';

        return $html;
    }
}
