<?php

namespace Klevu\Search\Plugin\Api\Search;

use Klevu\Search\Helper\Config as KlevuConfig;
use Klevu\Search\Helper\Data as KlevuHelperData;
use Magento\Framework\Registry;

/**
 * Class SearchResult
 * @package Klevu\Search\Plugin\Api\Search
 */
class SearchResult
{

    /**
     * @var KlevuHelperData
     */
    protected $klevuHelperData;

    /**
     * Registry instance
     */
    private $registry;

    /**
     * @var KlevuConfig
     */
    private $klevuConfig;

    /**
     * SearchResult constructor.
     *
     * @param Registry $registry
     * @param KlevuConfig $klevuConfig
     */
    public function __construct(Registry $registry, KlevuConfig $klevuConfig, KlevuHelperData $klevuHelperData)
    {
        $this->registry = $registry;
        $this->klevuConfig = $klevuConfig;
        $this->klevuHelperData = $klevuHelperData;
    }

    /**
     * @param \Magento\Framework\Api\Search\SearchResult $subject
     * @param $result
     * @return array
     */
    public function beforeSetItems(
        \Magento\Framework\Api\Search\SearchResult $subject,
        $result)
    {
        $current_order = $this->registry->registry('current_order');
        $klReqCleanerType = $this->registry->registry('klReqCleanerType');
        if ($this->klevuConfig->isPreserveLayoutLogEnabled()) {
            $process = rand(100000, 999999);
            $this->writeToPreserveLayoutLog(
                $process . sprintf(" searchResultPlugin:: currentRegistryOrder-%s", $current_order)
            );
            $this->writeToPreserveLayoutLog(
                $process . sprintf(" searchResultPlugin:: RequestType-%s", $klReqCleanerType)
            );
        }
        if (!empty($current_order)) {
            if ($current_order == "personalized") {
                if (!empty($this->registry->registry('search_ids'))) {
                    $flag = $key = 0;
                    $ids = array_reverse($this->registry->registry('search_ids'));
                    //this will fix any issues with non associated requests as the request is uninterrupted between cleaner and this point.
                    $this->registry->unregister('search_ids');
                    $result_key = array();
                    $returned_ids = array();
                    foreach ($result as $item) {
                        $key++;
                        if (in_array($item->getId(), $ids)) {
                            $score = $this->array_find($item->getId(), $ids, $key);
                        } else {
                            $score = $item->getCustomAttribute("score")->getValue() + (int)$key;
                        }
                        $item->getCustomAttribute("score")->setValue($score);
                        $returned_ids[] = $item->getId();
                        if ($item->getCustomAttribute("score")->getValue() !== null) {
                            $flag = 1;
                            $result_key[$item->getCustomAttribute("score")->getValue()] = $item->getCustomAttribute("score")->getValue();
                        }
                    }

                    if ($flag == 1) {
                        array_multisort($result_key, SORT_DESC, $result);
                    }

                    $from = $this->registry->registry('from');
                    $size = $this->registry->registry('size');
                    if ($this->klevuConfig->isPreserveLayoutLogEnabled()) {

                        $returned_ids_ordered = array();
                        foreach ($result as $item) {
                            $returned_ids_ordered[] = $item->getId();
                        }
                        $this->writeToPreserveLayoutLog(
                            $process . sprintf(" searchResultPlugin:: Array reverse search ids %s", implode(',', $ids))
                        );
                        $this->writeToPreserveLayoutLog(
                            $process . sprintf(" searchResultPlugin:: Search Result before processing : %s", implode(',', $returned_ids))
                        );
                        $this->writeToPreserveLayoutLog(
                            $process . sprintf(" searchResultPlugin:: Search Result after processing : %s", implode(',', $returned_ids_ordered))
                        );
                        $this->writeToPreserveLayoutLog(
                            $process . sprintf(" searchResultPlugin:: Offset of the list of results : from = %s", $from)
                        );
                        $this->writeToPreserveLayoutLog(
                            $process . sprintf(" searchResultPlugin:: Size of the results page : size =  %s", $size)
                        );
                    }
                    if (!empty($size)) {
                        $start = $from / $size;
                        if (count($result) > 0) {
                            $array = array_chunk($result, $size);
                            if (isset($array[$start])) {
                                return [$array[$start]];
                            }
                        } else {
                            return [$result];
                        }
                    } else {
                        return [$result];
                    }
                }
            }
        }


    }

    /**
     * Adding score value by 10K
     *
     * @param $needle
     * @param array $haystack
     * @param int $itemKey
     * @return int
     */
    public function array_find($needle, array $haystack, $itemKey = 0)
    {
        foreach ($haystack as $key => $value) {
            if ($value == $needle) {
                //powerup score by 10k ids
                return (int)$key + 10000;
            }
        }
        return $itemKey;
    }

    /**
     * Writes logs to the Klevu_Search_Preserve_Layout.log file
     *
     * @param $message
     */
    private function writeToPreserveLayoutLog($message)
    {
        $this->klevuHelperData->preserveLayoutLog($message);
    }
}

