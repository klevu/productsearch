<?php

namespace Klevu\Search\Model\System\Config\Source\Log;

use Klevu\Logger\Model\Source\LogLevel\Zend as ZendLogLevelSource;

/**
 * @deprecated Use Klevu\Logger\Model\Source\LogLevel\Zend
 * @see Klevu\Logger\Model\Source\LogLevel\Zend
 */
class Level
{
    /**
     * @var ZendLogLevelSource
     */
    private $zendLogLevelSource;

    /**
     * Level constructor.
     * @param ZendLogLevelSource $zendLogLevelSource
     */
    public function __construct(
        ZendLogLevelSource $zendLogLevelSource
    ) {
        $this->zendLogLevelSource = $zendLogLevelSource;
    }

    /**
     * @deprecated Use Klevu\Logger\Model\Source\LogLevel\Zend
     * @see Klevu\Logger\Model\Source\LogLevel\Zend::toOptionArray
     */
    public function toOptionArray()
    {
        return $this->zendLogLevelSource->toOptionArray();
    }
}
