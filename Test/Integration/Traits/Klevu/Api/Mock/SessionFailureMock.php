<?php

namespace Klevu\Search\Test\Integration\Traits\Klevu\Api\Mock;

use Klevu\Search\Model\Product\KlevuProductActions;
use PHPUnit\Framework\MockObject\MockObject;

trait SessionFailureMock
{
    /**
     * @return KlevuProductActions|MockObject
     */
    public function getKlevuProductActionsMockWithSessionFailure()
    {
        $mockKlevuProductActions = $this->getMockBuilder(KlevuProductActions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockKlevuProductActions->method('setupSession')
            ->willReturn(false);

        return $mockKlevuProductActions;
    }
}
