<?php

namespace Klevu\Search\Test\Unit\Model\Klevu\Cron;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Klevu\Cron\Scheduler;
use Klevu\Search\Model\Klevu\HelperManager;
use Magento\Cron\Model\ResourceModel\Schedule\Collection as ScheduleCollection;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * cron_schedule dates are UTC in Magento_Cron, so the scheduler must not use store local time.
 * Store local time put klevu rows hours in the past (missed) or the future (late) on non-UTC stores.
 */
class SchedulerTest extends TestCase
{
    /**
     * Allowed drift in seconds between the time the scheduler reads and the time the test reads
     */
    private const TOLERANCE = 5;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $mockTimezone;

    /**
     * @var ScheduleFactory|MockObject
     */
    private $mockScheduleFactory;

    /**
     * @var ScheduleCollection|MockObject
     */
    private $mockCollection;

    /**
     * @var array
     */
    private $collectionFilters = [];

    /**
     * @var string
     */
    private $originalTimezone;

    /**
     * Magento runs PHP in UTC (app/bootstrap.php), so the tests do too
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->originalTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
    }

    /**
     * @dataProvider storeTimezoneOffsetsDataProvider
     */
    public function testScheduleNowCreatesScheduleAtCurrentUtcTime($storeOffsetSeconds)
    {
        $this->setStoreOffset($storeOffsetSeconds);
        $mockSchedule = $this->getMockSchedule();
        $mockSchedule->expects($this->once())->method('trySchedule')->willReturn(true);
        $mockSchedule->expects($this->once())->method('save');
        $this->setupScheduleFactory(0, $mockSchedule);

        $this->getScheduler()->scheduleNow('klevu_search_product_sync');

        $this->assertSame('klevu_search_product_sync', $mockSchedule->getJobCode());
        $this->assertSame(Schedule::STATUS_PENDING, $mockSchedule->getStatus());
        $this->assertUtcNow($mockSchedule->getScheduledAt(), 'scheduled_at');
        $this->assertUtcNow($mockSchedule->getCreatedAt(), 'created_at');
    }

    /**
     * @dataProvider storeTimezoneOffsetsDataProvider
     */
    public function testScheduleNowChecksForPendingDuplicatesAroundCurrentUtcTime($storeOffsetSeconds)
    {
        $this->setStoreOffset($storeOffsetSeconds);
        $mockSchedule = $this->getMockSchedule();
        $mockSchedule->method('trySchedule')->willReturn(true);
        $this->setupScheduleFactory(0, $mockSchedule);

        $this->getScheduler()->scheduleNow('klevu_search_product_sync');

        $this->assertSame(Schedule::STATUS_PENDING, $this->collectionFilters['status']);
        $this->assertSame('klevu_search_product_sync', $this->collectionFilters['job_code']);
        $this->assertUtcNow($this->collectionFilters['scheduled_at']['from'], 'duplicate window start', -15 * 60);
        $this->assertUtcNow($this->collectionFilters['scheduled_at']['to'], 'duplicate window end', 15 * 60);
    }

    public function testScheduleNowDoesNotCreateScheduleWhenPendingDuplicateExists()
    {
        $this->setStoreOffset(0);
        $mockSchedule = $this->getMockSchedule();
        $mockSchedule->expects($this->never())->method('save');
        $this->setupScheduleFactory(1, $mockSchedule);

        $this->getScheduler()->scheduleNow('klevu_search_product_sync');

        $this->assertNull($mockSchedule->getScheduledAt());
    }

    /**
     * @dataProvider storeTimezoneOffsetsDataProvider
     */
    public function testIsRunningLooksBackOneHourFromCurrentUtcTime($storeOffsetSeconds)
    {
        $this->setStoreOffset($storeOffsetSeconds);
        $this->setupScheduleFactory(0, $this->getMockSchedule());

        $this->assertFalse($this->getScheduler()->isRunning('klevu_search_product_sync'));

        $this->assertSame(Schedule::STATUS_RUNNING, $this->collectionFilters['status']);
        $this->assertUtcNow($this->collectionFilters['executed_at']['gteq'], 'running window start', -60 * 60);
    }

    /**
     * @dataProvider storeTimezoneOffsetsDataProvider
     */
    public function testGetSchedulerTimeMysqlReturnsCurrentUtcTime($storeOffsetSeconds)
    {
        $this->setStoreOffset($storeOffsetSeconds);

        $this->assertUtcNow($this->getScheduler()->getSchedulerTimeMysql(), 'scheduler time');
    }

    /**
     * Offsets of the store timezone from UTC, as TimezoneInterface::scopeTimeStamp() would apply them
     *
     * @return array[]
     */
    public function storeTimezoneOffsetsDataProvider()
    {
        return [
            'UTC' => [0],
            'America/New_York (EDT, UTC-4)' => [-4 * 3600],
            'Asia/Kolkata (UTC+5:30)' => [19800],
        ];
    }

    /**
     * @param int $offsetSeconds
     *
     * @return void
     */
    private function setStoreOffset($offsetSeconds)
    {
        $this->mockTimezone = $this->getMockBuilder(TimezoneInterface::class)->getMock();
        $this->mockTimezone->method('scopeTimeStamp')->willReturnCallback(
            static function () use ($offsetSeconds) {
                return time() + $offsetSeconds;
            }
        );
    }

    /**
     * @param string|null $actual
     * @param string $label
     * @param int $offsetSeconds
     *
     * @return void
     */
    private function assertUtcNow($actual, $label, $offsetSeconds = 0)
    {
        $this->assertIsString($actual, $label . ' is not set');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $actual);
        $expected = time() + $offsetSeconds;
        $actualTimestamp = (new \DateTime($actual, new \DateTimeZone('UTC')))->getTimestamp();
        $this->assertLessThanOrEqual(
            self::TOLERANCE,
            abs($expected - $actualTimestamp),
            sprintf('%s %s is not UTC (expected about %s)', $label, $actual, gmdate('Y-m-d H:i:s', $expected))
        );
    }

    /**
     * @param int $pendingCount
     * @param Schedule|MockObject $mockSchedule
     *
     * @return void
     */
    private function setupScheduleFactory($pendingCount, $mockSchedule)
    {
        $this->collectionFilters = [];
        $this->mockCollection = $this->getMockBuilder(ScheduleCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'load', 'getSize'])
            ->getMock();
        $this->mockCollection->method('addFieldToFilter')->willReturnCallback(
            function ($field, $condition) {
                $this->collectionFilters[$field] = $condition;

                return $this->mockCollection;
            }
        );
        $this->mockCollection->method('load')->willReturnSelf();
        $this->mockCollection->method('getSize')->willReturn($pendingCount);

        $mockCollectionSource = $this->getMockSchedule();
        $mockCollectionSource->method('getCollection')->willReturn($this->mockCollection);

        $this->mockScheduleFactory = $this->getMockBuilder(ScheduleFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->mockScheduleFactory->method('create')
            ->willReturnOnConsecutiveCalls($mockCollectionSource, $mockSchedule);
    }

    /**
     * Schedule with real data accessors (setScheduledAt etc.) and stubbed persistence
     *
     * @return Schedule|MockObject
     */
    private function getMockSchedule()
    {
        return $this->getMockBuilder(Schedule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['trySchedule', 'save', 'getCollection'])
            ->getMock();
    }

    /**
     * @return Scheduler
     */
    private function getScheduler()
    {
        $mockConfigHelper = $this->getMockBuilder(ConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockConfigHelper->method('isExternalCronEnabled')->willReturn(true);

        $mockHelperManager = $this->getMockBuilder(HelperManager::class)
            ->disableOriginalConstructor()
            ->addMethods(['getConfigHelper'])
            ->getMock();
        $mockHelperManager->method('getConfigHelper')->willReturn($mockConfigHelper);

        $mockContext = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRegistry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new Scheduler(
            $mockContext,
            $mockRegistry,
            $this->mockTimezone,
            $this->mockScheduleFactory ?: $this->getMockBuilder(ScheduleFactory::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $mockHelperManager
        );
    }
}
