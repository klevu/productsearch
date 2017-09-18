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
           ['value' => static::NO, 'label' => __('Excluding Tax')],
           ['value' => static::YES, 'label' => __('Including Tax')],

        ];
    }
}
