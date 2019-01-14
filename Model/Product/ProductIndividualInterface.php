<?php
/**
 * ProductIndividualInterface
 */

namespace Klevu\Search\Model\Product;

interface ProductIndividualInterface
{
    /**
     * Function that is used to define mysql stings for Individually visible type
     * @return array
     */
    public function getProductIndividualTypeArray();
	
	/**
     * Function that is used to define mysql stings for Child type
     * @return array
     */
	public function getProductChildTypeArray();
}