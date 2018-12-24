<?php

namespace Klevu\Search\Plugin\Search\Block;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template\Context as Context;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Klevu\Search\Helper\Config as KlevuConfig;

class Result extends \Magento\Framework\View\Element\Template
{

    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_catalogLayer;

    const KLEVU_PRESERVE_LAYOUT    = 1;

    /**
     * @param Context $context
     * @param LayerResolver $layerResolver
     * @param array $data
     */
    public function __construct(
        Context $context,
        LayerResolver $layerResolver,
        KlevuConfig $klevuConfig,
        array $data = []
    ) {
        $this->_catalogLayer = $layerResolver->get();
        $this->_klevuConfig = $klevuConfig;
        parent::__construct($context, $data);
    }

    public function afterSetListOrders()
    {

        if($this->_klevuConfig->getCatalogSearchRelevance($this->_storeManager->getStore()) && $this->_klevuConfig->isLandingEnabled() == static::KLEVU_PRESERVE_LAYOUT) {
            $options = [];
            $category = $this->_catalogLayer->getCurrentCategory();
            /* @var $category \Magento\Catalog\Model\Category */
            $availableOrders = $category->getAvailableSortByOptions();
            unset($availableOrders['relevance']);
            unset($availableOrders['position']);
            $availableOrders['personalized'] = __('Relevance');
            $this->getLayout()->getBlock('search_result_list')->setAvailableOrders(
                $availableOrders
            )->setDefaultDirection(
                'desc'
            )->setDefaultSortBy(
                'personalized'
            );
            return $this;
        }
    }
}
   

