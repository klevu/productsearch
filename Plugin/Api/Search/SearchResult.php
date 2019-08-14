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

class SearchResult
{

    /**
     * Registry instance
     */
    private $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry,KlevuConfig $klevuConfig)
    {

        $this->registry = $registry;
        $this->klevuConfig = $klevuConfig;
    }

    public function beforeSetItems(\Magento\Framework\Api\Search\SearchResult $subject, $result)
    {

            $current_order = $this->registry->registry('current_order');
            if (!empty($current_order)) {
                if ($current_order == "personalized") {
                    if (!empty($this->registry->registry('search_ids'))) {
                        $flag = 0;
                        $ids = array_reverse($this->registry->registry('search_ids'));
                        $result_key = array();
                        foreach ($result as $item) {
                            if (in_array($item->getId(), $ids)) {
                                $score = $this->array_find($item->getId(), $ids);
                            } else {
                                $score = $item->getCustomAttribute("score")->getValue();
                            }
                            $item->getCustomAttribute("score")->setValue($score);

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

    public function array_find($needle, array $haystack)
    {
        foreach ($haystack as $key => $value) {
            if ($value == $needle) {
                return $key;
            }
        }
        return 0;
    }

}