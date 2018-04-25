<?php
/**
 * Klevu main sync model
 */
namespace Klevu\Search\Model;

use Klevu\Search\Model\Klevu\Cron\SchedulerInterface as SchedulerInterface;
use Klevu\Search\Model\Klevu\Category\CategoryInterface as CategoryInterface;
use Klevu\Search\Model\Klevu\HelperManager as KlevuHelperManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context as Magento_Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry as Magento_Registry;
use Magento\Framework\UrlInterface as Magento_UrlInterface;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;

use Zend\Log\Logger;
use Symfony\Component\Process\PhpExecutableFinder as PhpExecutableFinderFactory;
use Magento\Framework\Shell;

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
     * @var \Symfony\Component\Process\PhpExecutableFinder
     */
    protected $_phpExecutableFinder;
    protected $_phpPath = null;

    protected $_klevuHelperManager;
    protected $_klevuSchedulerInterface;
    protected $_klevuCategoryInterface;
    protected $_urlInterface;

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
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
		
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection,$data);
        $this->_klevuHelperManager = $klevuHelperManager;
        $this->_klevuSchedulerInterface = $klevuSchedulerInterface;
        $this->_klevuCategoryInterface = $klevuCategoryInterface;
        $this->_urlInterface = $urlInterface;
        $this->_shell = $shell;
        $this->_phpExecutableFinder = $phpExecutableFinderFactory;
		$this->directoryList = $directoryList;
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
    public function executeSubProcess($command){
       if(is_null($this->_phpPath)) $this->_phpPath = $this->_phpExecutableFinder->find() ?: 'php';
       try{
           $this->_shell->execute(
               $this->_phpPath . ' %s '.$command,
               [
                   BP . '/bin/magento'
               ]
           );
           return true;
       } catch (\Exception $e) {
           // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
           $this->log(Logger::CRIT, "can not execute subprocess $command");
           return false;
       }
    }
    /**
     * Get the klevu cron entry which is running mode
     *
     * @param string|null $jobCode
     * @return string|void
     */
    public function getKlevuCronStatus($jobCode = null)
    {
        if (is_null($jobCode)) {
            if ($this->getJobCode()) $jobCode = $this->getJobCode();
            $jobCode = $this->getDefaultJobCode();
        }
        $scheduler = $this->getScheduler();
        $filters = array(
            "job_code" => $jobCode,
            "status" => $scheduler->getStatusByCode('running')
        );
        $operations = array(
            "setPageSize" => 1

        );
		
		$files = glob($this->directoryList->getPath(DirectoryList::VAR_DIR).'/*.lock');
		$messagestr = '';
		if(!empty($files)) {
			foreach($files as $key => $value) {
				$params['filename'] = basename($value);
				$url_lock = $this->_urlInterface->getUrl("klevu_search/sync/clearlock",$params);
				$messagestr.=  "Lock File exits since ".date ("Y-m-d H:i:s", filemtime($value)) ." <a href='" . $url_lock . "'>Clear Klevu Lock File </a>".$params['filename']."</br>";
			}
		}
		
        $runningSchedules = $scheduler->getScheduleCollection($filters, $operations);
        if ($runningSchedules->getSize()) {
            $url = $this->_urlInterface->getUrl("klevu_search/sync/clearcron");
			
            $messagestr.= $scheduler->getStatusByCode('running') . " Since " . $runningSchedules->getFirstItem()->getData("executed_at") . " <a href='" . $url . "'>Clear Klevu Cron</a></br>";
        } else {
            $filters = array(
                "job_code" => $jobCode,
                "status" => $scheduler->getStatusByCode('success')
            );
            $operations = array(
                "setOrder" => array(
                    'finished_at',
                    'desc'
                ),
                "setPageSize" => 1

            );
            $doneSchedules = $scheduler->getScheduleCollection($filters, $operations);
            if ($doneSchedules->getSize()) {
                $messagestr.= $scheduler->getStatusByCode('success') . " " . $doneSchedules->getFirstItem()->getData("finished_at");
            }
        }
        return $messagestr;
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
     * Remove the cron which is in running state
     *
     * @param string|null $jobCode
     * @return void
     */
    public function clearKlevuCron($jobCode = null)
    {
        if (is_null($jobCode)) {
            if ($this->getJobCode()) $jobCode = $this->getJobCode();
            $jobCode = $this->getDefaultJobCode();
        }
        $scheduler = $this->getScheduler();
        $filters = array(
            "job_code" => $jobCode,
            "status" => $scheduler->getStatusByCode('running')
        );
        $runningSchedules = $scheduler->getScheduleCollection($filters);
        if ($runningSchedules->getSize()) {
            foreach ($runningSchedules as $record) {
                $record->delete();
            }
        }
    }
	
	/**
     * Remove lock file
     *
     * @param string|null $filename
     * @return void
     */
    public function clearKlevuLockFile($filename = null)
    {
		$fname = $this->directoryList->getPath(DirectoryList::VAR_DIR)."/".$filename;
		if(is_writable($fname)) {
			if(!unlink($fname)) {
			  return "Error deleting $filename";
			} else {
			  return "Deleted $filename";
			}
		} else {
			return "permissions denied for $filename";
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
            $this->log(Logger::INFO, "Memory limit reached. Stopped and rescheduled.");
            $cron_status = $this->_klevuHelperManager->getConfigHelper()->isExternalCronEnabled();
            if ($cron_status) {
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
        $php_memory_limit = ini_get('memory_limit');
        $usage = memory_get_usage(true);

        if ($php_memory_limit < 0) {
            $this->log(Logger::DEBUG, sprintf(
                "Memory usage: %s of %s.",
                $this->_klevuHelperManager->getDataHelper()->bytesToHumanReadable($usage),
                $php_memory_limit
            ));
            return true;
        }
        $limit = $this->_klevuHelperManager->getDataHelper()->humanReadableToBytes($php_memory_limit);

        $this->log(Logger::DEBUG, sprintf(
            "Memory usage: %s of %s.",
            $this->_klevuHelperManager->getDataHelper()->bytesToHumanReadable($usage),
            $this->_klevuHelperManager->getDataHelper()->bytesToHumanReadable($limit)
        ));

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
        $this->_klevuHelperManager->getDataHelper()->log($level, sprintf("[%s] %s", $this->getJobCode(), $message));

        return $this;
    }

    /**
     * Run a sync from cron at the specified time. Checks that a cron is not already
     * scheduled to run in the 15 minute interval before or after the given time first.
     *
     *
     * @return $this
     */
    public function schedule()
    {
        $this->_klevuSchedulerInterface->scheduleNow($this->getJobCode());
        return $this;
    }

    //TODO: replace these functions with actions to also select data
    public function getCategoryToDelete($storeId = null){
        return $this->_klevuCategoryInterface->categoryDelete($storeId);
    }
    public function getCategoryToUpdate($storeId = null){
        return $this->_klevuCategoryInterface->categoryUpdate($storeId);
    }
    public function getCategoryToAdd($storeId = null){
        return $this->_klevuCategoryInterface->categoryAdd($storeId);
    }
	
	// Get registry variable
	public function getRegistry(){
		return $this->_registry;
	}
	
	public function getHelper() {
		return $this->_klevuHelperManager;
	}

	public function setSessionVariable($key,$value){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$objectManager->get('Magento\Framework\Session\SessionManagerInterface')->setData($key, $value);
	}

	public function getSessionVariable($key){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		return $objectManager->get('Magento\Framework\Session\SessionManagerInterface')->getData($key);
	}
}
