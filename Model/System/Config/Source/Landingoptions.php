<?php

namespace Klevu\Search\Model\System\Config\Source;

class Landingoptions
{

    const YES    = 1;
    const NO     = 0;
    const KlEVULAND = 2;

    public function toOptionArray()
    {
        $check_preserve = \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config')->getFeatures();
        if (!empty($check_preserve['disabled'])) {
            if (strpos($check_preserve['disabled'], "preserves_layout") !== false) {
                return [
                   ['value' => static::NO, 'label' => __('Native')],
                   ['value' => static::KlEVULAND, 'label' => __('Klevu JS Theme (Recommended)')],
                ];
            } else {
                return [
                   ['value' => static::NO, 'label' => __('Native')],
                   ['value' => static::KlEVULAND, 'label' => __('Klevu JS Theme (Recommended)')],
                   ['value' => static::YES, 'label' => __('Preserve your Magento layout')],
                ];
            }
        } elseif (empty($check_preserve['disabled'])) {
            return [
               ['value' => static::NO, 'label' => __('Native')],
               ['value' => static::KlEVULAND, 'label' => __('Klevu JS Theme (Recommended)')],
               ['value' => static::YES, 'label' => __('Preserve your Magento layout')],
            ];
        } else {
            return [
                ['value' => static::NO, 'label' => __('Native')],
                ['value' => static::KlEVULAND, 'label' => __('Klevu JS Theme (Recommended)')],
            ];
        }
    }
}
