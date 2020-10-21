<?php

namespace Klevu\Search\Plugin\Api\Search;

use Magento\CatalogSearch\Model\ResourceModel\EngineInterface as EngineInterface;
use Magento\Framework\Api\AbstractSimpleObject;
use Magento\Framework\Api\SearchCriteriaInterface as BaseSearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface as ScopeInterface;
use Magento\Framework\App\Config\MutableScopeConfigInterface as MutableScopeConfigInterface;
use Klevu\Search\Helper\Config as KlevuConfig;
use Klevu\Search\Helper\Data as KlevuHelperData;

/**
 * Class SearchResult
 * @package Klevu\Search\Plugin\Api\Search
 */
class SearchResult
{

    /**
     * Registry instance
     */
    private $registry;

    /**
     * @var KlevuConfig
     */
    private $klevuConfig;

    /**
     * @var KlevuHelperData
     */
    protected $klevuHelperData;

    /**
     * SearchResult constructor.
     *
     * @param Registry $registry
     * @param KlevuConfig $klevuConfig
     */
    public function __construct(Registry $registry,KlevuConfig $klevuConfig,KlevuHelperData $klevuHelperData)
    {

        $this->registry = $registry;
        $this->klevuConfig = $klevuConfig;
        $this->klevuHelperData = $klevuHelperData;
    }

    public function beforeSetItems(\Magento\Framework\Api\Search\SearchResult $subject, $result)
    {
            $current_order = $this->registry->registry('current_order');
            if (!empty($current_order)) {
                if ($current_order == "personalized") {
                    if (!empty($this->registry->registry('search_ids'))) {
                        $flag = $key = 0;
                        $ids = array_reverse($this->registry->registry('search_ids'));

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
                        if($this->klevuConfig->isPreserveLayoutLogEnabled()) {
                            $process = rand(100000, 999999);
                            $returned_ids_ordered = array();
                            foreach ($result as $item) {
                                $returned_ids_ordered[] = $item->getId();
                            }
                            $this->klevuHelperData->preserveLayoutLog(
                                $process . sprintf(" Array reverse search ids in SearchResult plugin %s", implode(',', $ids))
                            );
                            $this->klevuHelperData->preserveLayoutLog(
                                $process . sprintf(" Search Result before processing in SearchResult plugin : %s", implode(',', $returned_ids))
                            );
                            $this->klevuHelperData->preserveLayoutLog(
                                $process . sprintf(" Search Result after processing in SearchResult plugin : %s", implode(',', $returned_ids_ordered))
                            );
                            $this->klevuHelperData->preserveLayoutLog(
                                $process . sprintf(" Offset of the list of results : from = %s", $from)
                            );
                            $this->klevuHelperData->preserveLayoutLog(
                                $process . sprintf(" Size of the results page : size =  %s", $size)
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

}
