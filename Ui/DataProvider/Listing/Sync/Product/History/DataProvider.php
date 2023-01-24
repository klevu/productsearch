<?php

namespace Klevu\Search\Ui\DataProvider\Listing\Sync\Product\History;

use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Model\Product\Sync\ResourceModel\History as HistoryResourceModel;
use Klevu\Search\Model\Product\Sync\ResourceModel\History\Collection as HistoryCollection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Psr\Log\LoggerInterface;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param HistoryCollection $collection
     * @param LoggerInterface $logger
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        HistoryCollection $collection,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->collection = $collection;
        $this->logger = $logger;
        $this->prepareUpdateUrl();
    }

    /**
     * @return array
     */
    public function getData()
    {
        $storeId = $this->getStoreId();
        $productId = $this->getProductId();
        $parentId = $this->getParentId();
        if (!$storeId || !$productId || null === $parentId) {
            return [
                'totalRecords' => 0,
                'items' => []
            ];
        }
        $this->prepareCollection($storeId, $productId, $parentId);
        $collection = $this->getCollection();
        if (!$collection->isLoaded()) {
            $collection->load();
        }

        return $collection->toArray();
    }

    /**
     * Passes filter_url_params param to ajax call that populates grid, in this case store_id
     *
     * @return void
     */
    private function prepareUpdateUrl()
    {
        if (!isset($this->data['config']['filter_url_params']) ||
            !is_array($this->data['config']['filter_url_params'])
        ) {
            return;
        }
        foreach ($this->data['config']['filter_url_params'] as $paramName => $paramValue) {
            if ('*' === $paramValue) {
                $paramValue = $this->request->getParam($paramName);
            }
            if ($paramValue) {
                $this->data['config']['update_url'] = sprintf(
                    '%s%s/%s/',
                    $this->data['config']['update_url'],
                    $paramName,
                    $paramValue
                );
            }
        }
    }

    /**
     * @param int $storeId
     * @param int $productId
     * @param int $parentId
     *
     * @return void
     */
    private function prepareCollection($storeId, $productId, $parentId)
    {
        $this->collection->addFieldToSelect(HistoryResourceModel::ENTITY_ID);
        $this->collection->addFieldToSelect(History::FIELD_SYNCED_AT);
        $this->collection->addFieldToSelect(History::FIELD_STORE_ID);
        $this->collection->addFieldToSelect(History::FIELD_PRODUCT_ID);
        $this->collection->addFieldToSelect(History::FIELD_PARENT_ID);
        $this->collection->addFieldToSelect(History::FIELD_ACTION);
        $this->collection->addFieldToSelect(History::FIELD_SUCCESS);
        $this->collection->addFieldToSelect(History::FIELD_MESSAGE);

        $this->collection->addFieldToFilter(History::FIELD_STORE_ID, ['eq' => $storeId]);
        $this->collection->addFieldToFilter(History::FIELD_PRODUCT_ID, ['eq' => $productId]);
        $this->collection->addFieldToFilter(History::FIELD_PARENT_ID, ['eq' => $parentId]);
    }

    /**
     * @return int|null
     */
    private function getStoreId()
    {
        $return = null;
        $storeId = $this->request->getParam('store');
        if (is_bool($storeId) || !is_scalar($storeId)) {
            $this->logger->error(
                __(
                    'Invalid Store ID provided. Expected string or int, received %1',
                    is_object($storeId) ? get_class($storeId) : gettype($storeId) // phpcs:ignore
                ),
                ['method' => __METHOD__]
            );

            return $return;
        }
        try {
            $store = $this->storeManager->getStore($storeId);
            $return = (int)$store->getId();
        } catch (NoSuchEntityException $exception) {
            $this->logger->error($exception->getMessage(), ['method' => __METHOD__]);
        }

        return $return;
    }

    /**
     * @return int|null
     */
    private function getProductId()
    {
        $entityIds = $this->getValidEntityParam();
        if (null === $entityIds) {
            return null;
        }
        $ids = explode('-', $entityIds);

        return isset($ids[1]) && is_numeric($ids[1]) ? (int)$ids[1] : null;
    }

    /**
     * @return int|null
     */
    private function getParentId()
    {
        $entityIds = $this->getValidEntityParam();
        if (null === $entityIds) {
            return null;
        }
        $ids = explode('-', $entityIds);

        return isset($ids[0]) && is_numeric($ids[0]) ? (int)$ids[0] : null;
    }

    /**
     * @return string|null
     */
    private function getValidEntityParam()
    {
        $entityIds = $this->request->getParam('unique_entity_id');
        if (!is_string($entityIds)) {
            $this->logger->error(
                __(
                    'Invalid unique_entity_id provided. Expected string or int, received %1',
                    is_object($entityIds) ? get_class($entityIds) : gettype($entityIds) // phpcs:ignore
                ),
                ['method' => __METHOD__]
            );

            return null;
        }

        return $entityIds;
    }
}
