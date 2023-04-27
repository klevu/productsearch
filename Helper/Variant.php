<?php

namespace Klevu\Search\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Registry;

class Variant extends AbstractHelper
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Return the Product ID against the Parent ID for configurable product
     *
     * @param int $parentID
     *
     * @return bool|int
     */
    public function getChildIdFromParent($parentID)
    {
        //This will check for parentChildIDs
        $parentIDs = $this->registry->registry('parentChildIDs');
        if (empty($parentIDs)) {
            //This will check for Cat Nav, used the same function to fetch product id using Parent ID
            $parentIDs = $this->registry->registry('parentChildIDsCatNav');
        }

        return is_array($parentIDs) && isset($parentIDs[$parentID])
            ? (int)$parentIDs[$parentID]
            : false;
    }
}
