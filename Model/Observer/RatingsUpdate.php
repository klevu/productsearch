<?php

namespace Klevu\Search\Model\Observer;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Api\Service\Catalog\Product\Review\UpdateRatingInterface;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Klevu\Search\Model\Product\Sync as ProductSync;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem;
use Magento\Review\Model\Rating;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;

class RatingsUpdate implements ObserverInterface
{
    /**
     * @var ProductSync
     */
    protected $_modelProductSync;
    /**
     * @var Filesystem
     */
    protected $_magentoFrameworkFilesystem;
    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;
    /**
     * @var ProductAction
     */
    protected $_modelProductAction;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var Rating
     */
    protected $_ratingModelRating;
    /**
     * @var EntityType
     */
    protected $_modelEntityType;
    /**
     * @var Attribute
     */
    protected $_modelEntityAttribute;
    /**
     * @var UpdateRatingInterface
     */
    private $updateRating;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param MagentoProductActionsInterface $modelProductSync
     * @param Filesystem $magentoFrameworkFilesystem
     * @param SearchHelper $searchHelperData
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param Rating $ratingModelRating
     * @param EntityType $modelEntityType
     * @param Attribute $modelEntityAttribute
     * @param ProductAction $modelProductAction
     * @param UpdateRatingInterface|null $updateRating
     * @param ProductRepositoryInterface|null $productRepository
     */
    public function __construct(
        MagentoProductActionsInterface $modelProductSync,
        Filesystem $magentoFrameworkFilesystem,
        SearchHelper $searchHelperData,
        StoreManagerInterface $storeModelStoreManagerInterface,
        Rating $ratingModelRating,
        EntityType $modelEntityType,
        Attribute $modelEntityAttribute,
        ProductAction $modelProductAction,
        UpdateRatingInterface $updateRating = null,
        ProductRepositoryInterface $productRepository = null
    ) {
        $this->_modelProductSync = $modelProductSync;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_searchHelperData = $searchHelperData;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_ratingModelRating = $ratingModelRating;
        $this->_modelEntityType = $modelEntityType;
        $this->_modelEntityAttribute = $modelEntityAttribute;
        $this->_modelProductAction = $modelProductAction;

        $objectManager = ObjectManager::getInstance();
        $this->updateRating = $updateRating ?: $objectManager->get(UpdateRatingInterface::class);
        $this->productRepository = $productRepository ?: $objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * Update the product ratings value in product attribute
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $review = $this->getReview($event);
            if (!$review || !$review->getEntityPkValue()) {
                return;
            }

            $product = $this->productRepository->getById((int)$review->getEntityPkValue());
            $this->updateRating->execute($product);

            /* mark product for update to sync data with klevu */
            $this->_modelProductSync->updateSpecificProductIds(
                [
                    $review->getEntityPkValue()
                ]
            );
        // Potentially undocumented issues in the process and save functionality above could throw exceptions of
        //  any type. While it's against Magento guidelines to catch generic exceptions, it's more important that
        //  observers don't kill execution flow for ancillary operations
        } catch (\Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf(
                    "%s Exception thrown in %s::%s - %s",
                    get_class($e),
                    __CLASS__,
                    __METHOD__,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * @param Event $event
     *
     * @return Review|null
     */
    private function getReview(Event $event)
    {
        $review = $event->getDataUsingMethod('object');
        if (!($review instanceof Review)) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_WARN,
                'Object passed in event must be instance of Review'
            );
            $review = null;
        }

        return $review;
    }
}
