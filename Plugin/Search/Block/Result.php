<?php

namespace Klevu\Search\Plugin\Search\Block;

use Klevu\Search\Helper\Config as KlevuConfig;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\CatalogSearch\Block\Result as CatalogSearchSubject;
use Magento\Framework\View\Element\Template\Context as Context;

class Result extends \Magento\Framework\View\Element\Template
{

    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_catalogLayer;

    const KLEVU_PRESERVE_LAYOUT = 1;

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
    )
    {
        $this->_catalogLayer = $layerResolver->get();
        $this->_klevuConfig = $klevuConfig;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterSetListOrders(CatalogSearchSubject $result)
    {
        //If Klevu Extension is not enabled for specific store view
        if (!$this->_klevuConfig->isExtensionEnabled($this->_storeManager->getStore())) {
            return $this;
        }

        //Check if store is using the PRESERVE LAYOUT Option and Catalog Search Relevance On
        if ($this->_klevuConfig->getCatalogSearchRelevance($this->_storeManager->getStore()) && $this->_klevuConfig->isLandingEnabled() == static::KLEVU_PRESERVE_LAYOUT) {
            $options = [];
            $category = $this->_catalogLayer->getCurrentCategory();
            /* @var $category \Magento\Catalog\Model\Category */

            $searchBlock = $result->getListBlock();
            $availableOrders = $category->getAvailableSortByOptions();
            unset($availableOrders['relevance']);
            unset($availableOrders['position']);
            $availableOrders['personalized'] = $this->getKlevuRelevanceLabel();

            $searchBlock->setAvailableOrders(
                $availableOrders
            )->setDefaultDirection(
                'desc'
            )->setDefaultSortBy(
                'personalized'
            );
            return $this;
        }
    }

    /** Returns the text label configured in the Klevu System configuration
     * @return \Magento\Framework\Phrase|string
     */
    protected function getKlevuRelevanceLabel()
    {
        return $this->_klevuConfig->getCatalogSearchRelevanceLabel($this->_storeManager->getStore());
    }
}
