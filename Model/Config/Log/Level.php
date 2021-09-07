<?php

namespace Klevu\Search\Model\Config\Log;

use Klevu\Logger\Constants as LoggerConstants;

class Level extends \Magento\Framework\Config\Data
{

    /**
     * Return the log level value. Return Klevu\Logger\Constants::ZEND_LOG_WARN as default, if none set.
     *
     * @return int
     */
    public function getValue()
    {
        $value = $this->getData('value');

        return ($value != null) ? (int)$value : LoggerConstants::ZEND_LOG_WARN;
    }
}
