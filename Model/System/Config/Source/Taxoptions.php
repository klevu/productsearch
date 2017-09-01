<?php

namespace Klevu\Search\Model\System\Config\Source;

class Taxoptions
{
    const YES    = 1;
    const NO     = 0;
    const ADMINADDED  = 2;

    public function toOptionArray()
    {
        return [
           ['value' => static::ADMINADDED, 'label' => __('Do not add tax in price as catalog prices entered by admin already include tax')],
           ['value' => static::NO, 'label' => __('Do not add tax in price as product prices are displayed without tax')],
           ['value' => static::YES, 'label' => __('Add relevant tax in price as product prices need to be displayed with tax')],

        ];
    }
}
