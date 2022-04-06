<?php

namespace Klevu\Search\Test\Unit\Service;

use Klevu\Search\Service\Sync\GetBatchSize;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetBatchSizeServiceTest extends TestCase
{
    public function testServiceReturnsDatabaseValueAsInt()
    {
        $mockStore = $this->getMockStore();
        $mockScopeConfig = $this->getMockConfigSetting('200');
        $batchSizeService = new GetBatchSize($mockScopeConfig);

        $this->assertSame(200, $batchSizeService->execute($mockStore));
    }

    public function testGetBatchSizeReturnsConstWhenDatabaseIsNotSet()
    {
        $mockStore = $this->getMockStore();
        $mockScopeConfig = $this->getMockConfigSetting(null);
        $batchSizeService = new GetBatchSize($mockScopeConfig);

        $this->assertSame(GetBatchSize::DEFAULT_BATCH_SIZE, $batchSizeService->execute($mockStore));
    }

    /**
     * @return StoreInterface|MockObject
     */
    private function getMockStore()
    {
        $mockStore = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStore->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        return $mockStore;
    }

    /**
     * @param string|null $willReturn
     *
     * @return ScopeConfigInterface|MockObject
     */
    private function getMockConfigSetting($willReturn)
    {
        $mockScopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockScopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($willReturn);

        return $mockScopeConfig;
    }
}
