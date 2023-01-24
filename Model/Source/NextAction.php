<?php

namespace Klevu\Search\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class NextAction implements OptionSourceInterface
{
    const ACTION_ADD = 'add';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_VALUE_ADD = 10;
    const ACTION_VALUE_UPDATE = 30;
    const ACTION_VALUE_DELETE = 20;

    /**
     * @var string[]
     */
    private $actions = [
        self::ACTION_VALUE_ADD => self::ACTION_ADD,
        self::ACTION_VALUE_UPDATE => self::ACTION_UPDATE,
        self::ACTION_VALUE_DELETE => self::ACTION_DELETE
    ];

    /**
     * @return string[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => '',
            ],[
                'value' => static::ACTION_VALUE_ADD,
                'label' => ucwords(static::ACTION_ADD),
            ], [
                'value' => static::ACTION_VALUE_UPDATE,
                'label' => ucwords(static::ACTION_UPDATE),
            ], [
                'value' => static::ACTION_VALUE_DELETE,
                'label' => ucwords(static::ACTION_DELETE),
            ],
        ];
    }
}
