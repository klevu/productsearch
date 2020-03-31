<?php

namespace Klevu\Search\Helper;

use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;
use Klevu\Search\Model\ProductCollection as Klevu_ProductCollection;
use Klevu\Search\Model\Product\Indexer as Klevu_ProductIndexer;
use Magento\Framework\App\Helper\Context;

/**
 * Klevu search Backend helper
 *
 * @SuppressWarnings(PHPMD)
 */
class Backend extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**#@+
     * Constants
     */
    const KLEVU_USE_COLLECTION_COUNT = 5000;
    /**#@-*/

    /**
     * @var Klevu_ProductCollection 
     */
    private $_klevuCollection;

    /**
     * Backend constructor.
     * @param Klevu_ProductCollection $klevuCollection
     * @param Klevu_ProductIndexer $klevuIndexer
     * @param Klevu_HelperManager $klevuHelperManager
     * @param Context $context
     */
    public function __construct(
        Klevu_ProductCollection $klevuCollection,
        Klevu_ProductIndexer $klevuIndexer,
        Klevu_HelperManager $klevuHelperManager,
        Context $context
    )
    {
        $this->_klevuCollection = $klevuCollection;
        $this->_klevuIndexer = $klevuIndexer;
        $this->_searchHelperConfig = $klevuHelperManager->getConfigHelper();
        $this->_searchHelperData = $klevuHelperManager->getDataHelper();
        parent::__construct($context);
    }

    /**
     * Get total number of products (max to 5001).
     *
     * @return intval
     */
    public function getProductCollectionCount()
    {
        return $this->_klevuCollection->loadProductCountByCollection();
    }


    /**
     * Recommend to Use Collection Method or not based on collection count.
     *
     * @return bool
     */
    public function getRecommendToUseCollectionMethod()
    {
        if ($this->getProductCollectionCount() > SELF::KLEVU_USE_COLLECTION_COUNT) {
            return true;
        }
        return false;
    }


    /**
     * Check Magento Indexers invalid or not
     *
     * @return bool
     */
    private function checkMagentoIndexersInvalid()
    {
        return empty($this->_klevuIndexer->getInvalidIndexers()) ? false : true;
    }

    /**
     * To show message if collection method is on and indexers are invalid
     *
     * @return bool
     */
    public function checkToShowIndexerMessage()
    {
        return ($this->_searchHelperConfig->isCollectionMethodEnabled() && $this->checkMagentoIndexersInvalid()) ? true : false;
    }
}
