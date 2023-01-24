<?php

namespace Klevu\Search\Api\Data;

use Klevu\Search\Api\Data\KlevuSyncEntityInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface SyncEntitySearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get attributes list.
     *
     * @return KlevuSyncEntityInterface[]
     */
    public function getItems();

    /**
     * Set attributes list.
     *
     * @param KlevuSyncEntityInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items);
}
