<?php
/**
 * Schedule wrapper model for use in synchronisation
 */
namespace Klevu\Search\Model\Klevu\Cron;


use Klevu\Search\Model\Klevu\Cron\SchedulerInterface as SchedulerInterface;
use Klevu\Search\Model\Klevu\HelperManager as KlevuHelperManager;
use Magento\Cron\Model\Schedule as Magento_Schedule;
use Magento\Cron\Model\ScheduleFactory as Magento_ScheduleFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context as Magento_Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry as Magento_Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface as TimezoneInterface;

class Scheduler extends AbstractModel implements SchedulerInterface
{
    protected $_timezone;
    protected $_scheduleFactory;
    protected $_klevuHelperManager;

    public function __construct(
        Magento_Context $context,
        Magento_Registry $registry,
        TimezoneInterface $timezone,
        Magento_ScheduleFactory $scheduleFactory,
        KlevuHelperManager $klevuHelperManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_timezone = $timezone;
        $this->_scheduleFactory = $scheduleFactory;
        $this->_klevuHelperManager = $klevuHelperManager;
    }


    /**
     * Generate a schedule to be executed in the next cron run for a specific job code
     * @param null $jobCode
     * @return mixed
     */
    public function scheduleNow($jobCode = null)
    {
        if (is_null($jobCode)) return false;
        $cron_status = $this->_klevuHelperManager->getConfigHelper()->isExternalCronEnabled();
        if ($cron_status) {
            $filters = array(
                'status' => Magento_Schedule::STATUS_PENDING,
                'job_code' => $jobCode,
                'scheduled_at' => [
                    "from" => $this->convertTimestampForMysql($this->_timezone->scopeTimeStamp() - (15 * 60)),
                    "to" => $this->convertTimestampForMysql($this->_timezone->scopeTimeStamp() + (15 * 60))
                ]

            );

            $pendingJobs = $this->getScheduleCollection($filters);

            if ($pendingJobs->getSize() == 0) {

                $operations = array(
                    "setJobCode" => $jobCode
                );

                $this->manageSchedule($operations);

            }
        }
        return $this;
    }

    /** Convert time for mysql use
     * @param $timestamp
     * @return string
     */
    private function convertTimestampForMysql($timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Get the collection of schedule objects for specific filters and operators
     * @param array $filters
     * @param array $operations
     * @return ScheduleCollection
     */
    public function getScheduleCollection($filters = [], $operations = [])
    {
        $jobsCollection = $this->_scheduleFactory->create()->getCollection();
        if (count($filters) > 0)
            foreach ($filters as $filterKey => $filterValue) {
                $jobsCollection->addFieldToFilter($filterKey, $filterValue);
            }
        if (count($operations) > 0) {
            foreach ($operations as $operationKey => $operationValue) {
                if (is_array($operationValue)) {
                    $jobsCollection->$operationKey(...$operationValue);
                } else {
                    $jobsCollection->$operationKey($operationValue);
                }
            }
        }
        $jobsCollection->load();
        return $jobsCollection;
    }

    /**
     * Edit a schedule object or create a new one
     * @param array $operations
     * @param bool $schedule
     * @return Schedule
     */
    public function manageSchedule($operations = [], $schedule = false)
    {
        if ($schedule) {
            if (count($operations) > 0)
                foreach ($operations as $operationKey => $operationValue) {
                    $schedule->$operationKey($operationValue);
                }
        } else {
            $schedule = $this->_scheduleFactory->create();

            $operations = array_merge($this->getDefaultScheduleData(), $operations);

            if (count($operations) > 0)
                foreach ($operations as $operationKey => $operationValue) {
                    $schedule->$operationKey($operationValue);
                }
        }

        return $this->saveSchedule($schedule);
    }

    /** Get default schedule attributes on creation of a schedule item
     * @return array
     */
    private function getDefaultScheduleData()
    {
        $operations = array(
            "setCronExpr" => "* * * * *",
            "setCreatedAt" => $this->convertTimestampForMysql($this->_timezone->scopeTimeStamp()),
            "setScheduledAt" => $this->convertTimestampForMysql($this->_timezone->scopeTimeStamp()),
            "setStatus" => Magento_Schedule::STATUS_PENDING
        );
        return $operations;
    }

    /**
     * Save a schedule object after checking the validity
     * @param bool $schedule
     * @return Schedule
     */
    private function saveSchedule($schedule = false)
    {
        if ($schedule) {
            if ($schedule->trySchedule()) $schedule->save();
        }
        return $schedule;
    }

    /**
     * Normalise the status of the Schedule
     * @param string $code
     * @return string
     */
    public function getStatusByCode($code = 'pending')
    {
        $status = array(
            "pending" => Magento_Schedule::STATUS_PENDING,
            "running" => Magento_Schedule::STATUS_RUNNING,
            "success" => Magento_Schedule::STATUS_SUCCESS,
            "missed" => Magento_Schedule::STATUS_MISSED,
            "error" => Magento_Schedule::STATUS_ERROR
        );
        return $status[$code];
    }

    /**
     * Check if the threshold of concurrent jobs running at this time for a given job code has been reached.
     * @param null $jobCode
     * @param int $copies
     * @return boolean
     */
    public function isRunning($jobCode = null, $copies = 1)
    {
        if (is_null($jobCode)) return false;
        $filters = array(
            'status' => Magento_Schedule::STATUS_RUNNING,
            'job_code' => $jobCode,
            'executed_at' => array("gteq" => $this->convertTimestampForMysql($this->_timezone->scopeTimeStamp() - (60 * 60)))
        );

        $runningJobs = $this->getScheduleCollection($filters);

        return $runningJobs->getSize() >= $copies;
    }

    /** Get current time for mysql use
     * @return string
     */
    public function getSchedulerTimeMysql()
    {
        return $this->convertTimestampForMysql($this->_timezone->scopeTimeStamp());
    }
}
