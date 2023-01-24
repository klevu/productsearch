<?php

namespace Klevu\Search\Ui\Component\Listing\Columns;

use Klevu\Search\Api\Service\Sync\Product\GetStoresWithSyncDisabledInterface;
use Klevu\Search\Model\Product\Sync\History;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class SyncActions extends Column
{
    const URL_PATH_KLEVU_SYNC = 'klevu_search/syncproduct/now';
    const URL_PATH_KLEVU_SCHEDULE = 'klevu_search/syncproduct/schedule';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var GetStoresWithSyncDisabledInterface
     */
    private $getStoresWithSyncDisabled;
    /**
     * @var string[]
     */
    private $storesWithSyncDisabled;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param GetStoresWithSyncDisabledInterface $getStoresWithSyncDisabled
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        GetStoresWithSyncDisabledInterface $getStoresWithSyncDisabled,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->getStoresWithSyncDisabled = $getStoresWithSyncDisabled;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        foreach ($dataSource['data']['items'] as &$item) {
            if ($this->isProductTypeConfigurable($item)) {
                continue;
            }
            $name = $this->getData('name');
            $item[$name]['history'] = $this->getHistoryData($item);
            if ($this->isStoreSyncDisabled($item)) {
                continue;
            }
            $item[$name]['sync'] = $this->getSyncData($item);
            if (empty($item['next_action'])) {
                $item[$name]['schedule'] = $this->getScheduleData($item);
            }
        }

        return $dataSource;
    }

    /**
     * @return string[]
     */
    private function getStoresWithSyncDisabled()
    {
        if (null === $this->storesWithSyncDisabled) {
            $this->storesWithSyncDisabled = $this->getStoresWithSyncDisabled->execute();
        }

        return $this->storesWithSyncDisabled;
    }

    /**
     * @param array $item
     *
     * @return bool
     */
    private function isProductTypeConfigurable(array $item)
    {
        if (!isset($item[ProductInterface::TYPE_ID])) {
            return false;
        }

        return $item[ProductInterface::TYPE_ID] === Configurable::TYPE_CODE;
    }

    /**
     * @param array $item
     *
     * @return bool
     */
    private function isStoreSyncDisabled(array $item)
    {
        return !isset($item[History::FIELD_STORE_ID]) ||
            array_key_exists($item[History::FIELD_STORE_ID], $this->getStoresWithSyncDisabled());
    }

    /**
     * @param array $item
     *
     * @return array
     */
    private function getHistoryData(array $item)
    {
        return [
            'callback' => [
                [
                    'provider' => 'sync_product_listing.sync_product_listing' .
                        '.sync_product_history_modal.sync_product_history_listing',
                    'target' => 'destroyInserted',
                ],
                [
                    'provider' => 'sync_product_listing.sync_product_listing' .
                        '.sync_product_history_modal.sync_product_history_listing',
                    'target' => 'updateData',
                    'params' => [
                        'unique_entity_id' => $item['unique_entity_id'],
                        'store' => $this->getStoreId()
                    ],
                ],
                [
                    'provider' => 'sync_product_listing.sync_product_listing.sync_product_history_modal',
                    'target' => 'openModal',
                ]
            ],
            'href' => '#',
            'label' => __('View History'),
            'hidden' => false,
        ];
    }

    /**
     * @param array $item
     *
     * @return array
     */
    private function getSyncData(array $item)
    {
        return [
            'href' => $this->urlBuilder->getUrl(
                self::URL_PATH_KLEVU_SYNC,
                ['id' => $this->getProductId($item), 'store' => $this->getStoreId()]
            ),
            'label' => __('Sync Now'),
            'hidden' => false,
            '__disableTmpl' => true,
        ];
    }

    /**
     * @param array $item
     *
     * @return array
     */
    private function getScheduleData(array $item)
    {
        return [
            'href' => $this->urlBuilder->getUrl(
                self::URL_PATH_KLEVU_SCHEDULE,
                ['id' => $this->getProductId($item), 'store' => $this->getStoreId()]
            ),
            'label' => __('Add to Next Scheduled Run'),
            'hidden' => false,
            '__disableTmpl' => true,
        ];
    }

    /**
     * @param array $item
     *
     * @return mixed
     */
    private function getProductId(array $item)
    {
        return isset($item['unique_entity_id']) ? $item['unique_entity_id'] : $item['entity_id'];
    }

    /**
     * @return string
     */
    private function getStoreId()
    {
        return $this->context->getRequestParam('store');
    }
}
