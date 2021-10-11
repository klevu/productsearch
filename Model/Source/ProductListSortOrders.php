<?php

namespace Klevu\Search\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model collating available sort order options
 * @link https://support.klevu.com/knowledgebase/sort-order-options/
 */
class ProductListSortOrders implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'rel',
                'label' => 'Relevance',
            ], [
                'value' => 'lth',
                'label' => 'Price: Low to high',
            ], [
                'value' => 'htl',
                'label' => 'Price: High to low',
            ], [
                'value' => 'NAME_ASC',
                'label' => 'Name: A-Z',
            ], [
                'value' => 'NAME_DESC',
                'label' => 'Name: Z-A',
            ], [
                'value' => 'RATING_ASC',
                'label' => 'Rating: Ascending',
            ], [
                'value' => 'RATING_DESC',
                'label' => 'Rating: Descending',
            ], [
                'value' => 'NEW_ARRIVAL_ASC',
                'label' => 'Newness: Old to New',
            ], [
                'value' => 'NEW_ARRIVAL_DESC',
                'label' => 'Newness: New to Old',
            ],
        ];
    }
}
