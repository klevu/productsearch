<?php
/**
 * Klevu product resource model collection
 */

namespace Klevu\Search\Model\Klevu\ResourceModel\Klevu;

use Klevu\Search\Api\Service\Sync\GetBatchSizeInterface;
use Klevu\Search\Model\Klevu\Klevu;
use Klevu\Search\Model\Klevu\Klevu as KlevuSync;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuSyncResourceModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Eav\Model\Entity;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection as MagentoCollection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;

class Collection extends AbstractCollection
{
    /**
     * @var KlevuSync
     */
    private $klevuSync;
    /**
     * @var GetBatchSizeInterface
     */
    private $getBatchSize;

    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(KlevuSync::class, KlevuSyncResourceModel::class);
    }

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     * @param KlevuSync|null $klevuSync
     * @param GetBatchSizeInterface|null $getBatchSize
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        AdapterInterface $connection = null,
        AbstractDb $resource = null,
        KlevuSync $klevuSync = null,
        GetBatchSizeInterface $getBatchSize = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->klevuSync = $klevuSync ?: ObjectManager::getInstance()->create(Klevu::class);
        $this->getBatchSize = $getBatchSize ?: ObjectManager::getInstance()->create(GetBatchSizeInterface::class);
    }

    /**
     * @param StoreInterface $store
     * @param string|null $type
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function initCollectionByType(
        StoreInterface $store,
        $type = KlevuSync::OBJECT_TYPE_PRODUCT
    ) {
        if (!$this->validateType($type)) {
            $type = KlevuSync::OBJECT_TYPE_PRODUCT;
        }
        $batchSize = $this->getBatchSize->execute($store);

        $this->addFieldToSelect(KlevuSync::FIELD_ENTITY_ID);
        $this->addFieldToSelect(KlevuSync::FIELD_PRODUCT_ID);
        $this->addFieldToSelect(KlevuSync::FIELD_PARENT_ID);
        $this->addFieldToFilter(KlevuSync::FIELD_TYPE, $type);
        $this->addFieldToFilter(KlevuSync::FIELD_STORE_ID, $store->getId());
        $this->setOrder(KlevuSync::FIELD_ENTITY_ID, MagentoCollection::SORT_ORDER_ASC);
        $this->setPageSize($batchSize);
        $this->_logger->debug(
            sprintf(
                'Klevu Data Sync Select: %s : %s',
                __METHOD__,
                $this->getSelect()->__toString()
            )
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function filterProductsToUpdate()
    {
        $this->join(
            ['product' => $this->getConnection()->getTableName('catalog_product_entity')],
            implode(' AND ', [
                "main_table." . KlevuSync::FIELD_PRODUCT_ID . " = product." . Entity::DEFAULT_ENTITY_ID_FIELD,
                "main_table." . KlevuSync::FIELD_LAST_SYNCED_AT . " < product." . ProductInterface::UPDATED_AT
            ]),
            ''
        );
        $this->_logger->debug(
            sprintf(
                'Products to Update Select: %s : %s',
                __METHOD__,
                $this->getSelect()->__toString()
            )
        );

        return $this;
    }

    /**
     * @param string $type
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function validateType($type)
    {
        if (!$type) {
            return false;
        }
        $types = $this->klevuSync->getTypes();
        if (is_string($type) && in_array($type, $types, true)) {
            return true;
        }
        throw new \InvalidArgumentException(
            __('Incorrect sync type supplied. Must be one of %s', implode(',', $types))
        );
    }
}
