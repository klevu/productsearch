<?php

namespace Klevu\Search\Model\Product;

use Magento\Framework\Indexer\ConfigInterface;
use Magento\Framework\Indexer\IndexerInterfaceFactory;


/**
 * Indexer model for checking index status
 *
 * @SuppressWarnings(PHPMD)
 */
class Indexer
{

    /**
     * Indexer constructor.
     * @param ConfigInterface $config
     * @param IndexerInterfaceFactory $indexerFactory
     */
    public function __construct(
        ConfigInterface $config,
        IndexerInterfaceFactory $indexerFactory
    )
    {
        $this->config = $config;
        $this->indexerFactory = $indexerFactory;

    }

    /**
     * Return indexer array
     *
     * @return array
     */
    protected function prepareIndexersToCheck()
    {
        $indexers = array(
            'catalog_category_product', 'catalog_product_category',
            'catalogrule_rule', 'catalogrule_product',
            'cataloginventory_stock',
            'catalog_product_attribute', 'catalog_product_price'
        );
        return $indexers;
    }

    /**
     * Return invalid indexer array
     *
     * @return array
     */
    public function getInvalidIndexers()
    {
        $invalidIndexers = array();
        foreach (array_keys($this->config->getIndexers()) as $indexerId) {
            if (in_array($indexerId, $this->prepareIndexersToCheck())) {
                /** @var \Magento\Indexer\Model\Indexer $indexer */
                $indexer = $this->indexerFactory->create();
                $indexer->load($indexerId);
                if ($indexer->isInvalid()) {
                    $invalidIndexers[] = $indexerId;
                }
            }
        }
        return $invalidIndexers;
    }

}
