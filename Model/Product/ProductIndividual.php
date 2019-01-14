<?php

namespace Klevu\Search\Model\Product;
use Magento\Framework\DataObject;

class ProductIndividual extends DataObject implements ProductIndividualInterface
{
    protected $_klevuParentProductType;
    protected $_context;

    public function __construct(
        array $data = []
    ){
        parent::__construct($data);
    }
	
    public function getProductIndividualTypeArray(){
        return array('simple','bundle','grouped','virtual','downloadable','giftcard');
    }
	
	public function getProductChildTypeArray(){
        return array('simple','virtual');
    }
}