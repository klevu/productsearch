<?php

namespace Klevu\Search\Block\Search\Index;

use Klevu\Search\Block\Search\Index as IndexBlock;
use Magento\Framework\View\Element\Template;

class Sync extends IndexBlock
{
    /**
     * @return string
     */
    protected function _toHtml()
    {
        // Skip direct parent which checks for JSv2 configuration
        //  to prevent backend blocks not displaying when JSv2 enabled
        return Template::_toHtml();
    }
}
