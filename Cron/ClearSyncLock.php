<?php

namespace Klevu\Search\Cron;

use Exception;
use Klevu\Search\Helper\Config as KlevuHelperConfig;
use Klevu\Search\Helper\Data as KlevuHelperData;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zend\Log\Logger;

/**
 * Class ClearSyncLock
 * @package Klevu\Search\Cron
 */
class ClearSyncLock
{
    const KLEVU_RUNNING_LOCK_FILE = 'klevu_running_index.lock';

    /**
     * @var KlevuHelperData
     */
    private $klevuHelperData;

    /**
     * @var KlevuHelperConfig
     */
    private $klevuHelperConfig;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var StoreManagerInterface
     */
    protected $storeInterface;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var Filesystem\Driver\File
     */
    protected $fileDriver;

    /**
     * ClearSyncLock constructor.
     * @param DirectoryList $directoryList
     * @param KlevuHelperData $klevuHelperData
     * @param KlevuHelperConfig $klevuHelperConfig
     * @param StoreManagerInterface $storeInterface
     * @param Filesystem $fileSystem
     * @param Filesystem\Driver\File $file
     */
    public function __construct(
        DirectoryList $directoryList,
        KlevuHelperData $klevuHelperData,
        KlevuHelperConfig $klevuHelperConfig,
        StoreManagerInterface $storeInterface,
        Filesystem $fileSystem,
        Filesystem\Driver\File $file
    )
    {
        $this->directoryList = $directoryList;
        $this->klevuHelperData = $klevuHelperData;
        $this->klevuHelperConfig = $klevuHelperConfig;
        $this->storeInterface = $storeInterface;
        $this->fileSystem = $fileSystem;
        $this->fileDriver = $file;
    }

    /**
     * Remove the Klevu related lock files if exists
     *
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        $selectedOption = (int)$this->klevuHelperConfig->getSelectedLockFileOption();
        if (is_null($selectedOption)) {
            return;
        }
        $prepareListFiles = [static::KLEVU_RUNNING_LOCK_FILE];
        $storeList = $this->storeInterface->getStores();
        foreach ($storeList as $store) {
            if ($store instanceof StoreInterface) {
                $prepareListFiles[] = $store->getCode() . '_' . self::KLEVU_RUNNING_LOCK_FILE;
            }
        }
        try {
            foreach ($prepareListFiles as $fileName) {
                $dirReader = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);
                if (!$dirReader->isExist($fileName)) {
                    continue;
                }

                //Current Unix timestamp
                $currentTime = time();
                $absPath = $dirReader->getAbsolutePath($fileName);
                //File Modification Time in Unix timestamp
                $fileModifiedTime = filemtime($absPath);
             
                //If file is older than selectionOption
                if ($this->fileDriver->isExists($absPath) && $currentTime - $fileModifiedTime > $selectedOption) {
                    $this->fileDriver->deleteFile($absPath);
                }
            }
        } catch (Exception $e) {
            $this->klevuHelperData->log(Logger::DEBUG, sprintf('Lock File Remove Error : %s', $e->getMessage()));
        }
    }


}

