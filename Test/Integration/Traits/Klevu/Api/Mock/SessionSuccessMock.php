<?php

namespace Klevu\Search\Test\Integration\Traits\Klevu\Api\Mock;

use Klevu\Search\Model\Product\KlevuProductActions;
use PHPUnit\Framework\MockObject\MockObject;

trait SessionSuccessMock
{
    /**
     * @return KlevuProductActions|MockObject
     */
    public function getKlevuProductActionsMockWithSessionSuccess()
    {
        $mockKlevuProductActions = $this->getMockBuilder(KlevuProductActions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockKlevuProductActions->method('setupSession')
            ->willReturn(true);

        return $mockKlevuProductActions;
    }
}
