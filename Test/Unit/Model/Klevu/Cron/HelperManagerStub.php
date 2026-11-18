<?php

namespace Klevu\Search\Test\Unit\Model\Klevu\Cron;

class HelperManagerStub extends \Klevu\Search\Model\Klevu\HelperManager
{
    /**
     * @return mixed
     */
    public function getConfigHelper()
    {
        return $this->__call('getConfigHelper', []);
    }
}
