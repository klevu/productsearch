<?php


namespace Klevu\Search\Model\System\Config\Source;

/**
 * Class NotificationOptions
 * @package Klevu\Search\Model\System\Config\Source
 */
class NotificationOptions
{
    const LOCK_WARNING_DISABLE = 0;
    const LOCK_WARNING_KLEVU_CONFIG = 1;
    const LOCK_WARNING_EVERY_ADMIN_PAGE = 2;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => static::LOCK_WARNING_DISABLE, 'label' => __('Disabled')],
            ['value' => static::LOCK_WARNING_KLEVU_CONFIG, 'label' => __('At the top of this Config screen only')],
            ['value' => static::LOCK_WARNING_EVERY_ADMIN_PAGE, 'label' => __('At the top of every Magento Admin page')]
        ];
    }


}

