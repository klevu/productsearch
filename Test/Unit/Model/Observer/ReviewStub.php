<?php

namespace Klevu\Search\Test\Unit\Model\Observer;

class ReviewStub extends \Magento\Review\Model\Review
{
    /**
     * @return mixed
     */
    public function getStatusId()
    {
        return $this->__call('getStatusId', []);
    }
    /**
     * @return mixed
     */
    public function getEntityPkValue()
    {
        return $this->__call('getEntityPkValue', []);
    }
}
