<?php

namespace Klevu\Search\Plugin\Api\Search;

use Klevu\Search\Helper\Config as KlevuConfig;
use Klevu\Search\Helper\Data as KlevuHelperData;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Api\Search\SearchResult as FrameworkSearchResult;
use Magento\Framework\Registry;

class SearchResult
{
    const KLEVU_SEARCH_RESULT_BOOSTING_VALUE = 10000;

    /**
     * @var KlevuHelperData
     */
    protected $klevuHelperData;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var KlevuConfig
     */
    private $klevuConfig;
    /**
     * @var int
     */
    private $boostingValue;

    /**
     * SearchResult constructor.
     *
     * @param Registry $registry
     * @param KlevuConfig $klevuConfig
     * @param KlevuHelperData $klevuHelperData
     * @param int|null $boostingValue
     */
    public function __construct(
        Registry $registry,
        KlevuConfig $klevuConfig,
        KlevuHelperData $klevuHelperData,
        $boostingValue = self::KLEVU_SEARCH_RESULT_BOOSTING_VALUE
    ) {
        $this->registry = $registry;
        $this->klevuConfig = $klevuConfig;
        $this->klevuHelperData = $klevuHelperData;
        $this->boostingValue = $boostingValue;
    }

    /**
     * @param FrameworkSearchResult $subject
     * @param DocumentInterface[] $items
     *
     * @return DocumentInterface[][]
     */
    public function beforeSetItems(
        FrameworkSearchResult $subject,
        array $items
    ) {
        $currentOrder = $this->registry->registry('current_order');
        $logProcessId = rand(100000, 999999);
        $this->logBefore($currentOrder, $logProcessId);
        $searchIds = $this->registry->registry('search_ids');
        if ($currentOrder !== "personalized" || empty($searchIds) || !count($items)) {
            return [$items];
        }

        $ids = $this->getSearchIdsInReverse($searchIds);
        $isSortRequired = false;
        $key = 0;
        $resultKey = [];
        $returnedIds = [];
        foreach ($items as $item) {
            $key++;
            $customAttributeScore = $item->getCustomAttribute("score");
            if (null === $customAttributeScore) {
                // this should not be possible
                $this->logMissingScoreError($item, $logProcessId);
                continue;
            }
            $returnedIds[] = (int)$item->getId();
            if (in_array((int)$item->getId(), $ids, true)) {
                $score = $this->array_find((int)$item->getId(), $ids, $key);
            } else {
                $score = (int)$customAttributeScore->getValue() + (int)$key;
            }
            $customAttributeScore->setValue($score);
            if ($customAttributeScore->getValue() === null) {
                continue;
            }
            $scoreValue = $customAttributeScore->getValue();
            $resultKey[(string)$scoreValue] = $scoreValue;
            $isSortRequired = true;
        }
        if ($isSortRequired) {
            array_multisort($resultKey, SORT_DESC, $items);
        }

        $this->logAfter($items, $ids, $returnedIds, $logProcessId);
        $pageSize = (int)$this->registry->registry('size');
        if (!empty($pageSize)) {
            $startFromItemNumber = (int)$this->registry->registry('from');
            if (empty($startFromItemNumber)) {
                $startFromItemNumber = 1;
            }
            $pages = array_chunk($items, $pageSize);
            $pageKey = (int)floor($startFromItemNumber / $pageSize);
            if (isset($pages[$pageKey])) {
                return [$pages[$pageKey]];
            }
        }

        return [$items];
    }

    /**
     * @param array $searchIds
     *
     * @return int[]
     */
    private function getSearchIdsInReverse(array $searchIds)
    {
        $reversedSearchIds = array_reverse($searchIds);

        $return = array_map(static function ($id) {
            return (int)$id;
        }, $reversedSearchIds);
        // this will fix any issues with non associated requests
        // as the request is uninterrupted between cleaner and this point.
        $this->registry->unregister('search_ids');

        return $return;
    }

    /**
     * Adding score value by 10K
     *
     * @param int $needle
     * @param int[] $haystack
     * @param int $itemKey
     *
     * @return int
     */
    public function array_find($needle, array $haystack, $itemKey = 0) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps, Generic.Files.LineLength.TooLong
    {
        foreach ($haystack as $key => $value) {
            if ($value === $needle) {
                // boost score by 10k ids
                return (int)$key + $this->boostingValue;
            }
        }

        return (int)$itemKey;
    }

    /**
     * @param string|null $currentOrder
     * @param int $logProcessId
     *
     * @return void
     */
    private function logBefore($currentOrder, $logProcessId)
    {
        if (!$this->klevuConfig->isPreserveLayoutLogEnabled()) {
            return;
        }
        $klReqCleanerType = $this->registry->registry('klReqCleanerType');
        $this->writeToPreserveLayoutLog(
            $logProcessId . sprintf(" searchResultPlugin:: currentRegistryOrder: [%s]", $currentOrder)
        );
        $this->writeToPreserveLayoutLog(
            $logProcessId . sprintf(" searchResultPlugin:: RequestType: [%s]", $klReqCleanerType)
        );
    }

    /**
     * @param DocumentInterface[] $items
     * @param int[] $ids
     * @param int[] $returnedIds
     * @param int $logProcessId
     *
     * @return void
     */
    private function logAfter(array $items, array $ids, array $returnedIds, $logProcessId)
    {
        if (!$this->klevuConfig->isPreserveLayoutLogEnabled()) {
            return;
        }
        $from = $this->registry->registry('from');
        $size = $this->registry->registry('size');
        $returnedIdsOrdered = [];
        foreach ($items as $item) {
            $returnedIdsOrdered[] = $item->getId();
        }
        $this->writeToPreserveLayoutLog(
            $logProcessId . sprintf(
                " searchResultPlugin:: Array reverse search ids %s",
                implode(',', $ids)
            )
        );
        $this->writeToPreserveLayoutLog(
            $logProcessId . sprintf(
                " searchResultPlugin:: Search Result before processing : %s",
                implode(',', $returnedIds)
            )
        );
        $this->writeToPreserveLayoutLog(
            $logProcessId . sprintf(
                " searchResultPlugin:: Search Result after processing : %s",
                implode(',', $returnedIdsOrdered)
            )
        );
        $this->writeToPreserveLayoutLog(
            $logProcessId . sprintf(" searchResultPlugin:: Offset of the list of results : from = %s", $from)
        );
        $this->writeToPreserveLayoutLog(
            $logProcessId . sprintf(" searchResultPlugin:: Size of the results page : size =  %s", $size)
        );
    }

    /**
     * Writes logs to the Klevu_Search_Preserve_Layout.log file
     *
     * @param string $message
     */
    private function writeToPreserveLayoutLog($message)
    {
        $this->klevuHelperData->preserveLayoutLog($message);
    }

    /**
     * @param DocumentInterface $item
     * @param int $logProcessId
     *
     * @return void
     */
    private function logMissingScoreError(DocumentInterface $item, $logProcessId)
    {
        $this->writeToPreserveLayoutLog(
            $logProcessId . sprintf(
                " searchResultPlugin:: Item returned in Search Result does not have"
                . " CustomAttribute 'score', skipping item : ItemID = %s",
                $item->getId()
            )
        );
    }
}
