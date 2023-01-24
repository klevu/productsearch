<?php

namespace Klevu\Search\Model\Klevu;

use Klevu\Search\Helper\Compat as Klevu_Helper_Compat;
use Klevu\Search\Helper\Config as Klevu_Helper_Config;
use Klevu\Search\Helper\Data as Klevu_Helper_Data;
use Klevu\Search\Helper\Image as Klevu_Helper_Image;
use Klevu\Search\Helper\Price as Klevu_Helper_Price;
use Klevu\Search\Helper\Stock as Klevu_Helper_Stock;
use Magento\Framework\DataObject;

class HelperManager extends DataObject
{
    /**
     * @param Klevu_Helper_Compat $compatHelper
     * @param Klevu_Helper_Image $imageHelper
     * @param Klevu_Helper_Price $priceHelper
     * @param Klevu_Helper_Stock $stockHelper
     * @param Klevu_Helper_Config $configHelper
     * @param Klevu_Helper_Data $dataHelper
     */
    public function __construct(
        Klevu_Helper_Compat $compatHelper,
        Klevu_Helper_Image $imageHelper,
        Klevu_Helper_Price $priceHelper,
        Klevu_Helper_Stock $stockHelper,
        Klevu_Helper_Config $configHelper,
        Klevu_Helper_Data $dataHelper
    ) {
        $data = [
            'compat_helper' => $compatHelper,
            'image_helper' => $imageHelper,
            'price_helper' => $priceHelper,
            'stock_helper' => $stockHelper,
            'config_helper' => $configHelper,
            'data_helper' => $dataHelper
        ];
        parent::__construct($data);
    }
}
