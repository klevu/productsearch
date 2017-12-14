<?php
/**
 * Schedule wrapper interface for use in synchronisation
 */
namespace Klevu\Search\Model\Klevu\Cron;


interface SchedulerInterface
{
    /**
     * Generate a schedule to be executed in the next cron run for a specific job code
     * @param null $jobCode
     * @return mixed
     */
    public function scheduleNow( $jobCode = null );

    /**
     * Check if the threshold of concurrent jobs running at this time for a given job code has been reached.
     * @param null $jobCode
     * @param int $copies
     * @return boolean
     */
    public function isRunning($jobCode = null , $copies = 1);

    /**
     * Get the collection of schedule objects for specific filters and operators
     * @param array $filters
     * @param array $operations
     * @return ScheduleCollection
     */
    public function getScheduleCollection($filters = [],$operations = []);

    /**
     * Edit a schedule object or create a new one
     * @param array $operations
     * @param bool $schedule
     * @return Schedule
     */
    public function manageSchedule($operations = [],$schedule = false);

    /**
     * Normalise the status of the Schedule
     * @param string $code
     * @return string
     */
    public function getStatusByCode($code = 'pending');

    /** Get current time for mysql use
     * @return string
     */
    public function getSchedulerTimeMysql();
}