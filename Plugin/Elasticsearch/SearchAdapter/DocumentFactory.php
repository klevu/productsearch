<?php

namespace Klevu\Search\Plugin\Elasticsearch\SearchAdapter;

use Magento\Framework\Registry;

class DocumentFactory
{
    /**
     * Registry instance
     */
    private $registry;

    /**
     * @param Registry $registry
     */
    public function __construct( Registry $registry)
    {

        $this->registry = $registry;
    }
    public function afterCreate(
        $rawDocument,
        $result
    ) {
        try
        {
            $documentId = $result->getId();
            if(!empty($this->registry->registry('search_ids'))) {
                $sessionOrder = array_reverse($this->registry->registry('search_ids'));
            } else {
                $sessionOrder = array();
            }
            if(in_array($documentId,$sessionOrder)){
                $score = $this->array_find($documentId,$sessionOrder);
            } else {
                $score = $result->getCustomAttribute("score")->getValue();
            }
            $result->getCustomAttribute("score")->setValue($score);
        } catch (\Exception $e){}
        return $result;
    }


    public function array_find($needle, array $haystack)
    {
        foreach ($haystack as $key => $value) {
            if (false !== stripos($value, $needle)) {
                return $key;
            }
        }
        return 0;
    }
}
