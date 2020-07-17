<?php

namespace Klevu\Search\Model\System\Config\Source;
/**
 * Class LockFileOptions
 * @package Klevu\Search\Model\System\Config\Source
 */
class LockFileOptions
{

    const FILE_6_HOURS_AGO = 21600;
    const FILE_12_HOURS_AGO = 43200;
    const FILE_24_HOURS_AGO = 86400;
    const FILE_2_DAYS_AGO = 172800;
    const FILE_1_WEEK_AGO = 604800;


    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __(' -- Please Select Option -- ')],
            ['value' => static::FILE_6_HOURS_AGO, 'label' => __('6 Hours ago')],
            ['value' => static::FILE_12_HOURS_AGO, 'label' => __('12 Hours ago')],
            ['value' => static::FILE_24_HOURS_AGO, 'label' => __('24 Hours ago')],
            ['value' => static::FILE_2_DAYS_AGO, 'label' => __('2 Days ago')],
            ['value' => static::FILE_1_WEEK_AGO, 'label' => __('1 Week ago')]

        ];
    }

}
