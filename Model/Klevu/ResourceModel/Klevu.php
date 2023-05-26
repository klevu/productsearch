<?php
/**
 * Klevu product resource model
 */

namespace Klevu\Search\Model\Klevu\ResourceModel;

use Klevu\Search\Api\Service\Sync\GetBatchSizeInterface;
use Klevu\Search\Model\Klevu\Klevu as KlevuSync;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\Collection as KlevuSyncCollection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;

class Klevu extends AbstractDb
{
    const TABLE = 'klevu_product_sync';
    /**
     * @var GetBatchSizeInterface
     */
    private $getBatchSize;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, KlevuSync::FIELD_ENTITY_ID);
    }

    /**
     * @param Context $context
     * @param string|null $connectionName
     * @param GetBatchSizeInterface|null $getBatchSize
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Context $context,
        $connectionName = null,
        GetBatchSizeInterface $getBatchSize = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($context, $connectionName);
        $objectManager = ObjectManager::getInstance();
        $this->getBatchSize = $getBatchSize ?: $objectManager->create(GetBatchSizeInterface::class);
        $this->logger = $logger ?: $objectManager->get(LoggerInterface::class);
    }

    /**
     * @param KlevuSyncCollection $collection
     * @param StoreInterface $store
     * @param array|null $productIds
     * @param int|null $lastEntityId
     *
     * @return array
     */
    public function getBatchDataForCollection(
        $collection,
        StoreInterface $store,
        $productIds = [],
        $lastEntityId = null
    ) {
        $connection = $collection->getConnection();
        $select = clone $collection->getSelect();
        $filter = [];
        if ($productIds) {
            $select->where('main_table.' . KlevuSync::FIELD_PRODUCT_ID . ' IN (?)', $productIds);
        }
        if (null !== $lastEntityId) {
            $batchSize = $this->getBatchSize->execute($store);
            $select->where('main_table.' . KlevuSync::FIELD_ENTITY_ID . ' > ?', $lastEntityId);
            $select->limit($batchSize);
        }
        $select->order('main_table.' . KlevuSync::FIELD_ENTITY_ID . ' ASC');

        $return = $connection->fetchAll($select, $filter);
        $this->logger->debug(
            sprintf('Batch Klevu Data Sync Collection Select: %s', $select->__toString())
        );
        unset($connection, $select);

        return $return;
    }
}
