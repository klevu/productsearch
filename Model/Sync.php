<?php
/**
 * Klevu main sync model
 */

namespace Klevu\Search\Model;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Model\Klevu\Category\CategoryInterface as CategoryInterface;
use Klevu\Search\Model\Klevu\Cron\SchedulerInterface as SchedulerInterface;
use Klevu\Search\Model\Klevu\HelperManager as KlevuHelperManager;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Filesystem\Glob as FileSystemGlob;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context as Magento_Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry as Magento_Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Shell;
use Magento\Framework\UrlInterface as Magento_UrlInterface;
use Symfony\Component\Process\PhpExecutableFinder as PhpExecutableFinderFactory;

class Sync extends AbstractModel
{

    /**
     * Limit the memory usage of the sync to 80% of the memory
     * limit. Considering that the minimum memory requirement
     * for Magento at the time of writing is 256MB, this seems
     * like a sensible default.
     */
    const MEMORY_LIMIT = 0.7;
    /**
     * @var Shell
     */
    protected $_shell;
    /**
     * @var PhpExecutableFinderFactory
     */
    protected $_phpExecutableFinder;
    /**
     * @var null
     */
    protected $_phpPath = null;
    /**
     * @var KlevuHelperManager
     */
    protected $_klevuHelperManager;
    /**
     * @var SchedulerInterface
     */
    protected $_klevuSchedulerInterface;
    /**
     * @var CategoryInterface
     */
    protected $_klevuCategoryInterface;
    /**
     * @var Magento_UrlInterface
     */
    protected $_urlInterface;
    /**
     * @var DirectoryList
     */
    private $directoryList;
    /**
     * @var FileDriver
     */
    protected $fileDriver;
    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;
    /**
     * @var FileSystemGlob
     */
    protected $fileSystemGlob;

    /**
     * @param Magento_Context $context
     * @param Magento_Registry $registry
     * @param KlevuHelperManager $klevuHelperManager
     * @param SchedulerInterface $klevuSchedulerInterface
     * @param CategoryInterface $klevuCategoryInterface
     * @param Magento_UrlInterface $urlInterface
     * @param DirectoryList $directoryList
     * @param Shell $shell
     * @param PhpExecutableFinderFactory $phpExecutableFinderFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param DriverInterface|null $fileDriver
     * @param SessionManagerInterface|null $sessionManager
     * @param FileSystemGlob|null $fileSystemGlob
     */

    public function __construct(
        Magento_Context $context,
        Magento_Registry $registry,
        KlevuHelperManager $klevuHelperManager,
        SchedulerInterface $klevuSchedulerInterface,
        CategoryInterface $klevuCategoryInterface,
        Magento_UrlInterface $urlInterface,
        DirectoryList $directoryList,
        Shell $shell,
        PhpExecutableFinderFactory $phpExecutableFinderFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = [],
        ?DriverInterface $fileDriver = null,
        ?SessionManagerInterface $sessionManager = null,
        ?FileSystemGlob $fileSystemGlob = null
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_klevuHelperManager = $klevuHelperManager;
        $this->_klevuSchedulerInterface = $klevuSchedulerInterface;
        $this->_klevuCategoryInterface = $klevuCategoryInterface;
        $this->_urlInterface = $urlInterface;
        $this->_shell = $shell;
        $this->_phpExecutableFinder = $phpExecutableFinderFactory;
        $this->directoryList = $directoryList;
        $objectManager = ObjectManager::getInstance();
        $this->fileDriver = $fileDriver
            ?: $objectManager->get(FileDriver::class);
        $this->sessionManager = $sessionManager
            ?: $objectManager->get(SessionManagerInterface::class);
        $this->fileSystemGlob = $fileSystemGlob
            ?: $objectManager->get(FileSystemGlob::class);
    }

    /**
     * Check if a sync is currently running from cron. A number of running copies to
     * check for can be specified, which is useful if checking if another copy of sync
     * is running from sync itself.
     *
     * Ignores processes that have been running for more than an hour as they are likely
     * to have crashed.
     *
     * @param int $copies
     *
     * @return bool
     */
    public function isRunning($copies = 1)
    {
        return $this->_klevuSchedulerInterface->isRunning($this->getJobCode(), $copies);
    }

    /**
     * @param string $command
     *
     * @return bool
     * @throws FileSystemException
     * @throws \Exception
     */
    public function executeSubProcess($command)
    {
        if ($this->_phpPath === null) {
            $this->_phpPath = $this->_phpExecutableFinder->find()
                ?: 'php';
        }
        try {
            $this->_shell->execute(
                $this->_phpPath . ' %s ' . $command,
                [
                    BP . '/bin/magento',
                ]
            );

            return true;
        } catch (\Exception $e) {
            $logDir = $this->directoryList->getPath(DirectoryList::VAR_DIR);
            $this->fileDriver->fileOpen($logDir . "/klevu_subprocess.lock", 'w');
            $this->log(
                LoggerConstants::ZEND_LOG_CRIT,
                "Can not execute subprocess $command " . $e->getMessage()
            );
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    public function getKlevuLockStatus()
    {
        $lockFileMessages = [];

        $files = $this->fileSystemGlob->glob(
            $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/*klevu_running_index.lock'
        );
        if (empty($files)) {
            return 'No lock files found.';
        }
        foreach ($files as $key => $value) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            $params['filename'] = basename($value);
            $urlLock = $this->_urlInterface->getUrl("klevu_search/sync/clearlock", $params);
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            $lockFileMessages[] = date('Y-m-d H:i:s', filemtime($value)) . ' - <a title="Remove Lock" href="'
                . $urlLock . '">Remove Lock</a><br />' . $params['filename'];
        }

        return implode('<br/><br/>', $lockFileMessages) . '<br/><br/>';
    }

    /**
     * Get the klevu cron entry which is running mode
     *
     * @param string|null $jobCode
     *
     * @return string
     */
    public function getKlevuCronStatus($jobCode = null)
    {
        if ($jobCode === null) {
            if ($this->getJobCode()) {
                $jobCode = $this->getJobCode();
            }
            $jobCode = $this->getDefaultJobCode();
        }
        $scheduler = $this->getScheduler();
        $filters = [
            "job_code" => $jobCode,
            "status" => $scheduler->getStatusByCode('running'),
        ];
        $operations = [
            "setPageSize" => 1,
        ];

        $messageToShow = '';
        $runningSchedules = $scheduler->getScheduleCollection($filters, $operations);
        if ($this->_klevuHelperManager->getConfigHelper()->isExternalCronActive()) {
            $messageToShow .= "Disabled";
        } elseif ($runningSchedules->getSize()) {
            $url = $this->_urlInterface->getUrl("klevu_search/sync/clearcron");

            $messageToShow .= $runningSchedules->getFirstItem()->getData("executed_at") . " - Running - <a href='"
                . $url . "'>Reset Klevu Cron</a>";
        } else {
            $filters = [
                "job_code" => $jobCode,
                "status" => $scheduler->getStatusByCode('success'),
            ];
            $operations = [
                "setOrder" => [
                    'finished_at',
                    'desc',
                ],
                "setPageSize" => 1,

            ];
            $doneSchedules = $scheduler->getScheduleCollection($filters, $operations);
            if ($doneSchedules->getSize()) {
                $messageToShow .= $doneSchedules->getFirstItem()->getData("finished_at") . ' - Completed';
            }
        }

        return $messageToShow;
    }

    /**
     * @return string
     */
    private function getDefaultJobCode()
    {
        return 'klevu_search_product_sync';
    }

    /**
     * @return SchedulerInterface
     */
    public function getScheduler()
    {
        return $this->_klevuSchedulerInterface;
    }

    /**
     * Remove the cron, which is in running state
     *
     * @param string|null $jobCode
     *
     * @return void
     */
    public function clearKlevuCron($jobCode = null)
    {
        if (null === $jobCode) {
            if ($this->getJobCode()) {
                $jobCode = $this->getJobCode();
            }
            $jobCode = $this->getDefaultJobCode();
        }
        $scheduler = $this->getScheduler();
        $filters = [
            "job_code" => $jobCode,
            "status" => $scheduler->getStatusByCode('running'),
        ];
        $runningSchedules = $scheduler->getScheduleCollection($filters);
        if ($runningSchedules->getSize()) {
            foreach ($runningSchedules as $record) {
                $record->delete();
            }
        }
    }

    /**
     * Remove a lock file
     *
     * @param string|null $filename
     *
     * @return string
     * @throws FileSystemException
     */
    public function clearKlevuLockFile($filename = null)
    {
        $fullFileName = $this->directoryList->getPath(DirectoryList::VAR_DIR) . "/" . $filename;
        if (!$this->fileDriver->isWritable($fullFileName)) {
            return sprintf("Permissions denied for lock file (%s) deletion. ", $filename);
        }
        if (!$this->fileDriver->deleteFile($fullFileName)) {
            return sprintf("Error while deleting lock file (%s) deleted. ", $filename);
        } else {
            return sprintf("Lock file (%s) deleted. ", $filename);
        }
    }

    /**
     * Check if the memory limit has been reached and reschedule to run
     * again immediately if so.
     *
     * @return bool true if a new process was scheduled, false otherwise.
     */
    public function rescheduleIfOutOfMemory()
    {
        if (!$this->isBelowMemoryLimit()) {
            $this->log(LoggerConstants::ZEND_LOG_INFO, "Memory limit reached. Stopped and rescheduled.");
            $cronStatus = $this->_klevuHelperManager->getConfigHelper()->isExternalCronEnabled();
            if ($cronStatus) {
                $this->schedule();
            }

            return true;
        }

        return false;
    }

    /**
     * Check if the current memory usage is below the limit.
     *
     * @return bool
     */
    protected function isBelowMemoryLimit()
    {
        $memoryLimit = ini_get('memory_limit');
        $usage = memory_get_usage(true);

        if ($memoryLimit < 0) {
            $this->log(
                LoggerConstants::ZEND_LOG_DEBUG,
                sprintf(
                    "Memory usage: %s of %s.",
                    $this->_klevuHelperManager->getDataHelper()->bytesToHumanReadable($usage),
                    $memoryLimit
                )
            );

            return true;
        }
        $limit = $this->_klevuHelperManager->getDataHelper()->humanReadableToBytes($memoryLimit);

        $this->log(
            LoggerConstants::ZEND_LOG_DEBUG,
            sprintf(
                "Memory usage: %s of %s.",
                $this->_klevuHelperManager->getDataHelper()->bytesToHumanReadable($usage),
                $this->_klevuHelperManager->getDataHelper()->bytesToHumanReadable($limit)
            )
        );

        if ($usage / $limit > static::MEMORY_LIMIT) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Write a message to the log file.
     *
     * @param int $level
     * @param string $message
     *
     * @return $this
     */
    public function log($level, $message)
    {
        $this->_klevuHelperManager->getDataHelper()->log(
            $level,
            sprintf("[%s] %s", $this->getJobCode(), $message)
        );

        return $this;
    }

    /**
     * Run a sync from cron at the specified time. Checks that a cron is not already
     * scheduled to run in the 15-minute interval before or after the given time first.
     *
     *
     * @return $this
     */
    public function schedule()
    {
        $this->_klevuSchedulerInterface->scheduleNow($this->getJobCode());

        return $this;
    }

    /**
     * @param mixed $storeId
     *
     * @return mixed
     */
    public function getCategoryToDelete($storeId = null)
    {
        return $this->_klevuCategoryInterface->categoryDelete($storeId);
    }

    /**
     * @param mixed $storeId
     *
     * @return bool|mixed
     */
    public function getCategoryToUpdate($storeId = null)
    {
        return $this->_klevuCategoryInterface->categoryUpdate($storeId);
    }

    /**
     * @param mixed $storeId
     *
     * @return bool|mixed
     */
    public function getCategoryToAdd($storeId = null)
    {
        return $this->_klevuCategoryInterface->categoryAdd($storeId);
    }

    /**
     * @return Magento_Registry
     */
    public function getRegistry()
    {
        return $this->_registry;
    }

    /**
     * @return KlevuHelperManager
     */
    public function getHelper()
    {
        return $this->_klevuHelperManager;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function setSessionVariable($key, $value)
    {
        $this->sessionManager->setData($key, $value);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getSessionVariable($key)
    {
        return $this->sessionManager->getData($key);
    }
}
