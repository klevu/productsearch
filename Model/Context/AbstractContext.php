<?php

namespace Klevu\Search\Model\Context;

use Magento\Framework\DataObject;


abstract class AbstractContext extends DataObject
{

    public function __construct(
        $data = []
    )
    {
        parent::__construct($data);
    }

    public function processOverrides(&$data){
        return $this;
    }

}
