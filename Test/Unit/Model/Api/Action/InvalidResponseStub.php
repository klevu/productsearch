<?php

namespace Klevu\Search\Test\Unit\Model\Api\Action;

class InvalidResponseStub extends \Klevu\Search\Model\Api\Response\Invalid
{
    /**
     * @return mixed
     */
    public function setErrors($errors)
    {
        return $this->__call('setErrors', [$errors]);
    }
}
