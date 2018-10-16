<?php
/**
 * Klevu override of the entity storage for use on preserve layout
 */
namespace Klevu\Search\Framework\Dynamic;

use Magento\Framework\Registry as MagentoRegistry;

class EntityStorage extends \Magento\Framework\Search\Dynamic\EntityStorage
{
    /**
     * @var mixed
     */
    private $source;

    /**
     * @param mixed $source
     * @param MagentoRegistry $magentoRegistry
     */
    public function __construct(
        $source,
        MagentoRegistry $magentoRegistry
    )
    {
		$sessionOrder = $magentoRegistry->registry('search_ids');
		if(is_array($sessionOrder) && is_array($source))
		{
			$result = array();
			foreach($sessionOrder as $val){
				if(array_search($val, $source) !== false) $result[] = $val; // adding values
			}
		} else {
			$result = $source;
		}
		
        $this->source = $result;
    }
    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }
}
