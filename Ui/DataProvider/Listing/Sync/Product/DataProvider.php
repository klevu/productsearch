<?php

namespace Klevu\Search\Ui\DataProvider\Listing\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Account\IntegrationStatusInterface;
use Klevu\Search\Api\Service\Sync\Product\GetNextActionsInterface;
use Klevu\Search\Api\Service\Sync\Product\GetStoresWithSyncDisabledInterface;
use Klevu\Search\Api\Ui\DataProvider\Listing\Sync\GetCollectionInterface;
use Klevu\Search\Model\Klevu\Klevu;
use Klevu\Search\Model\Source\NextAction;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Entity;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Psr\Log\LoggerInterface;

class DataProvider extends AbstractDataProvider
{
    const CATALOG_PRODUCT_EDIT_ROUTE = 'catalog/product/edit';
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var GetStoresWithSyncDisabledInterface
     */
    private $getStoresWithSyncDisabled;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    private $nextActions;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var GetNextActionsInterface
     */
    private $getNextActions;
    /**
     * @var IntegrationStatusInterface
     */
    private $integrationStatus;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param GetCollectionInterface $getCollection
     * @param RequestInterface $request
     * @param GetStoresWithSyncDisabledInterface $getStoresWithSyncDisabled
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param GetNextActionsInterface $getNextActions
     * @param LoggerInterface $logger
     * @param IntegrationStatusInterface $integrationStatus
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        GetCollectionInterface $getCollection,
        RequestInterface $request,
        GetStoresWithSyncDisabledInterface $getStoresWithSyncDisabled,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        GetNextActionsInterface $getNextActions,
        LoggerInterface $logger,
        IntegrationStatusInterface $integrationStatus,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;
        $this->collection = $getCollection->execute();
        $this->prepareUpdateUrl();
        $this->getStoresWithSyncDisabled = $getStoresWithSyncDisabled;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->getNextActions = $getNextActions;
        $this->logger = $logger;
        $this->integrationStatus = $integrationStatus;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $arrItems = [
            'totalRecords' => 0,
            'items' => [],
        ];
        $store = $this->getStore();
        if (!$store
            || !$store->getId()
            || !$this->integrationStatus->isIntegrated($store)
            || $this->isStoreSyncDisabled($store->getId())) {
            return $arrItems;
        }

        $collection = $this->getCollection();
        if (!$collection->isLoaded()) {
            $collection->load();
        }

        // collection of grouped products does not return a size, does have a count though.
        $arrItems['totalRecords'] = $collection->getSize() ?: count($collection);

        $items = $collection->toArray();
        $this->nextActions = $this->preLoadNextActions($store, $items);

        foreach ($items as $entityId => $item) {
            try {
                $arrItems['items'][] = $item + [
                        'next_action' => $this->getNextAction($item),
                        'sync_id' => $this->getSyncId($item),
                        'unique_entity_id' => $this->getUniqueId($item, $entityId),
                        'link' => $this->getLink($item)
                    ];
            } catch (InvalidArgumentException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return $arrItems;
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
     * @return StoreInterface|null
     */
    private function getStore()
    {
        $return = null;
        $storeId = $this->request->getParam('store');
        if (!$storeId) {
            return $return;
        }
        try {
            $return = $this->storeManager->getStore($storeId);
        } catch (NoSuchEntityException $exception) {
            $this->logger->error($exception->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);
        }

        return $return;
    }

    /**
     * @param string $storeId
     *
     * @return bool
     */
    private function isStoreSyncDisabled($storeId)
    {
        if (!$storeId) {
            return true;
        }
        $disabledStores = $this->getStoresWithSyncDisabled->execute();

        return array_key_exists($storeId, $disabledStores);
    }

    /**
     * @param array $data
     *
     * @return int|null
     */
    private function getNextAction(array $data)
    {
        if (isset($data[ProductInterface::TYPE_ID]) && Configurable::TYPE_CODE === $data[ProductInterface::TYPE_ID]) {
            return null;
        }
        $productId = $this->getProductIdentifier($data);

        if ($this->isNextAction($productId, NextAction::ACTION_DELETE)) {
            return NextAction::ACTION_VALUE_DELETE;
        }
        if ($this->isNextAction($productId, NextAction::ACTION_ADD)) {
            return NextAction::ACTION_VALUE_ADD;
        }
        if ($this->isNextAction($productId, NextAction::ACTION_UPDATE)) {
            return NextAction::ACTION_VALUE_UPDATE;
        }

        return null;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function getProductIdentifier(array $data)
    {
        $key = $this->getParentId($data) . '-';
        $key .= $this->getProductId($data) ?: '0';

        return $key;
    }

    /**
     * @param string $productId
     * @param string $action
     *
     * @return bool
     */
    private function isNextAction($productId, $action)
    {
        return isset($this->nextActions[$action]) &&
            is_array($this->nextActions[$action]) &&
            array_key_exists($productId, $this->nextActions[$action]);
    }

    /**
     * @param StoreInterface $store
     * @param array $items
     *
     * @return array
     */
    private function preLoadNextActions(StoreInterface $store, array $items)
    {
        if (empty($this->nextActions)) {
            $productIds = array_map(function ($item) {
                return $this->getProductId($item);
            }, $items);
            $parentIds = array_map(function ($item) {
                return $this->getParentId($item);
            }, $items);

            $itemIds = array_unique(
                array_merge(
                    array_values($productIds),
                    array_values($parentIds)
                )
            );
            $this->nextActions = $this->getNextActions->execute($store, $itemIds);
        }

        return $this->nextActions;
    }

    /**
     * @param array $item
     *
     * @return string
     */
    private function getSyncId(array $item)
    {
        $syncId = $this->getProductId($item);
        $parentId = $this->getParentId($item);
        if ($parentId !== '0') {
            $syncId .= ' (' . $parentId . ')';
        }

        return $syncId;
    }

    /**
     * @param string $entityId
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function getEntityId($entityId)
    {
        if (!is_string($entityId)) {
            throw new InvalidArgumentException(
                __(
                    'Invalid entity parameter: %1. Must be type string. Throw at %2::%3',
                    $entityId,
                    __CLASS__,
                    __METHOD__
                )
            );
        }
        $entityIds = explode('-', $entityId);

        return $entityIds[1] . '-' . $entityIds[0];
    }

    /**
     * @param array $item
     * @param string $entityId
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function getUniqueId(array $item, $entityId)
    {
        $entityId = $this->getEntityId($entityId);
        $rowId = (isset($item[Klevu::FIELD_ALIAS_ENTITY_ID])) ? $item[Klevu::FIELD_ALIAS_ENTITY_ID] : '0';

        return $entityId . '-' . $rowId;
    }

    /**
     * @param array $item
     *
     * @return string
     */
    private function getLink(array $item)
    {
        $storeId = isset($item[Klevu::FIELD_STORE_ID]) ? $item[Klevu::FIELD_STORE_ID] : null;
        $entityId = isset($item[Entity::DEFAULT_ENTITY_ID_FIELD]) ? $item[Entity::DEFAULT_ENTITY_ID_FIELD] : null;
        if (!$storeId || !$entityId) {
            return '';
        }

        return $this->urlBuilder->getUrl(
            self::CATALOG_PRODUCT_EDIT_ROUTE,
            [
                'id' => $entityId,
                'store' => $storeId
            ]
        );
    }

    /**
     * @param array $item
     *
     * @return string
     */
    private function getParentId(array $item)
    {
        if (isset($item[Klevu::FIELD_PARENT_ID])) {
            $parentId = $item[Klevu::FIELD_PARENT_ID];
        } elseif (isset($item[Klevu::FIELD_PRODUCT_PARENT_ID])) {
            $parentId = $item[Klevu::FIELD_PRODUCT_PARENT_ID];
        } else {
            $parentId = '0';
        }

        return $parentId;
    }

    /**
     * @param array $item
     *
     * @return string|null
     */
    private function getProductId(array $item)
    {
        $entityId = isset($item[Entity::DEFAULT_ENTITY_ID_FIELD])
            ? $item[Entity::DEFAULT_ENTITY_ID_FIELD]
            : null;

        return isset($item[Klevu::FIELD_PRODUCT_ID]) ?
            $item[Klevu::FIELD_PRODUCT_ID] :
            $entityId;
    }
}
