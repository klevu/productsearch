<?php

namespace Klevu\Search\Block\Search;

use Klevu\Search\Api\SerializerInterface;
use Klevu\Search\Helper\Config as KlevuConfig;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

class ConfigurableSortBy extends Template
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var OptionSourceInterface
     */
    private $productListSortOrdersSource;

    /**
     * ConfigurableSortBy constructor.
     * @param Context $context
     * @param SerializerInterface $serializer
     * @param OptionSourceInterface $productListSortOrdersSource
     * @param array $data
     */
    public function __construct(
        Context $context,
        SerializerInterface $serializer,
        OptionSourceInterface $productListSortOrdersSource,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->serializer = $serializer;
        $this->productListSortOrdersSource = $productListSortOrdersSource;
    }

    /**
     * @return array[]
     */
    public function getProductListSortOrderOptions()
    {
        if (!$this->getData('product_list_sort_order_options')) {
            $sortOrderOptions = [];

            try {
                $store = $this->_storeManager->getStore();
                $storeId = (int)$store->getId();
            } catch (NoSuchEntityException $e) {
                $this->_logger->error($e->getMessage());
                $storeId = 0;
            }

            $configValueUnserialized = trim((string)$this->_scopeConfig->getValue(
                KlevuConfig::XML_PATH_CATALOG_SEARCH_SORT_ORDERS,
                ScopeInterface::SCOPE_STORES,
                $storeId
            ));
            if ($configValueUnserialized) {
                $sortOrderOptions = $this->processUnserializedConfigValue($configValueUnserialized);
            }

            $this->setData('product_list_sort_order_options', $sortOrderOptions);
        }

        return $this->getData('product_list_sort_order_options');
    }

    /**
     * @param string $configValueUnserialized
     * @return array
     */
    private function processUnserializedConfigValue($configValueUnserialized)
    {
        $return = [];
        try {
            $sourceSortOrderOptions = $this->productListSortOrdersSource->toOptionArray();
            $sourceSortOrderOptionsHash = array_combine(
                array_column($sourceSortOrderOptions, 'value'),
                array_column($sourceSortOrderOptions, 'label')
            );

            $return = array_map(
                function ($row) use ($sourceSortOrderOptionsHash) {
                    $processedRow = [
                        'value' => isset($row['value']) ? $row['value'] : '',
                        'label' => isset($row['label']) ? trim($row['label']) : '',
                    ];
                    if (!$processedRow['label']) {
                        $processedRow['label'] = isset($sourceSortOrderOptionsHash[$processedRow['value']])
                            ? $sourceSortOrderOptionsHash[$processedRow['value']]
                            : null;
                    }

                    return $processedRow;
                },
                array_values($this->serializer->unserialize($configValueUnserialized))
            );

            $return = array_filter($return, static function ($row) {
                return $row['value'] && $row['label'];
            });
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $return;
    }
}