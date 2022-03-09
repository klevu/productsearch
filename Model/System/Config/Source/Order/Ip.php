<?php

namespace Klevu\Search\Model\System\Config\Source\Order;

use Magento\Framework\Data\OptionSourceInterface;

class Ip implements OptionSourceInterface {

    const ORDER_REMOTE_IP = "remote_ip";
    const ORDER_X_FORWARDED_FOR = "x_forwarded_for";

    /**
     * {@inheritdoc}
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => static::ORDER_REMOTE_IP,
                'label' => 'Remote Ip',
            ],
            [
                'value' => static::ORDER_X_FORWARDED_FOR,
                'label' => 'X_forwarded_for',
            ],
        ];
    }
}
