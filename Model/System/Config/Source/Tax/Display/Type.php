<?php

namespace Klevu\Search\Model\System\Config\Source\Tax\Display;

class Type implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var array
     */
    protected $_options;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = [];
            $this->_options[] = [
                'value' => \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX,
                'label' => __('Excluding Tax'),
            ];
            $this->_options[] = [
                'value' => \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX,
                'label' => __('Including Tax'),
            ];
        }
        return $this->_options;
    }
}
