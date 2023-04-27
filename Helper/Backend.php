<?php

namespace Klevu\Search\Helper;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;
use Klevu\Search\Model\Product\Indexer as Klevu_ProductIndexer;
use Klevu\Search\Model\ProductCollection as Klevu_ProductCollection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Glob as FilesystemGlob;

class Backend extends AbstractHelper
{
    const KLEVU_USE_COLLECTION_COUNT = 5000;

    /**
     * @var Klevu_ProductCollection
     */
    private $_klevuCollection;
    /**
     * @var Klevu_ProductIndexer
     */
    protected $_klevuIndexer;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;
    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * Backend constructor.
     *
     * @param Klevu_ProductCollection $klevuCollection
     * @param Klevu_ProductIndexer $klevuIndexer
     * @param Klevu_HelperManager $klevuHelperManager
     * @param DirectoryList $directoryList
     * @param Filesystem $fileSystem
     * @param Context $context
     */
    public function __construct(
        Klevu_ProductCollection $klevuCollection,
        Klevu_ProductIndexer $klevuIndexer,
        Klevu_HelperManager $klevuHelperManager,
        DirectoryList $directoryList,
        Filesystem $fileSystem,
        Context $context
    ) {
        $this->_klevuCollection = $klevuCollection;
        $this->_klevuIndexer = $klevuIndexer;
        $this->_searchHelperConfig = $klevuHelperManager->getConfigHelper();
        $this->_searchHelperData = $klevuHelperManager->getDataHelper();
        $this->directoryList = $directoryList;
        $this->fileSystem = $fileSystem;
        parent::__construct($context);
    }

    /**
     * Get total number of products (max to 5001).
     *
     * @return int
     */
    public function getProductCollectionCount()
    {
        return $this->_klevuCollection->loadProductCountByCollection();
    }

    /**
     * Recommend to Use Collection Method or not based on collection count.
     *
     * @return bool
     */
    public function getRecommendToUseCollectionMethod()
    {
        return $this->getProductCollectionCount() > self::KLEVU_USE_COLLECTION_COUNT;
    }

    /**
     * Check Magento Indexers invalid or not
     *
     * @return bool
     */
    private function checkMagentoIndexersInvalid()
    {
        return !empty($this->_klevuIndexer->getInvalidIndexers());
    }

    /**
     * To show message if collection method is on and indexers are invalid
     *
     * @return bool
     */
    public function checkToShowIndexerMessage()
    {
        return $this->_searchHelperConfig->isCollectionMethodEnabled() && $this->checkMagentoIndexersInvalid();
    }

    /**
     * Checks whethere any outdated lock file exists
     *
     * @return bool
     */
    public function isOutdatedLockFilesExist()
    {
        $flag = false;
        $selectedOption = $this->_searchHelperConfig->getSelectedLockFileOption();
        if (null === $selectedOption) {
            return false;
        }

        try {
            //checking all lock files
            $dirReader = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $files = FilesystemGlob::glob(
                $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/*klevu_running_index.lock'
            );

            if (!empty($files)) {
                foreach ($files as $key => $file) {
                    //not showing if file do not exist
                    if (!$dirReader->isExist($file)) {
                        continue;
                    }
                    //Current Unix timestamp
                    $currentTime = time();
                    $absPath = $dirReader->getAbsolutePath($file);
                    //File Modification Time in Unix timestamp
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    $fileModifiedTime = filemtime($absPath);

                    //Only show notification if lock file is older than selectionOption
                    if ($currentTime - $fileModifiedTime > $selectedOption) {
                        $flag = true;
                        break;
                    }
                }
            }

            return $flag;
        } catch (\Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf("Klevu Lock File Notification Exception: %s", $e->getMessage())
            );
        }

        return $flag;
    }
}
