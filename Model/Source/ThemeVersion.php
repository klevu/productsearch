<?php

namespace Klevu\Search\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ThemeVersion implements OptionSourceInterface
{
    const V1 = 'v1';
    const V2 = 'v2';

    /**
     * {@inheritdoc}
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => static::V1,
                'label' => 'Legacy JS Theme (aka JSv1)',
            ], [
               'value' => static::V2,
               'label' => 'Klevu JS Library Theme (aka JSv2)',
            ],
        ];
    }
}
