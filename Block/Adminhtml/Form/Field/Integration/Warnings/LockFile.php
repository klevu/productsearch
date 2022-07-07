<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration\Warnings;

use Klevu\Search\Service\Account\HelpArticleService;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Glob;
use Magento\Framework\Filesystem\Io\File as FileSystemIo;
use Magento\Framework\Filesystem\Io\IoInterface;

class LockFile extends Template
{
    const SUBPROCESS_LOCK_FILE = 'klevu_subprocess.lock';
    const KLEVU_SEARCH_SYNC_CLEARLOCK_PATH = "klevu_search/sync/clearlock";

    /**
     * @var DirectoryList
     */
    private $directoryList;
    /**
     * @var array
     */
    private $lockFiles;
    /**
     * @var IoInterface
     */
    private $fileSystemIo;
    /**
     * @var mixed
     */
    private $fileInfo;

    public function __construct(
        Context $context,
        array $data = [],
        DirectoryList $directoryList = null,
        IoInterface $fileSystemIo = null
    ) {
        $this->directoryList = $directoryList ?: ObjectManager::getInstance()->get(DirectoryList::class);
        $this->fileSystemIo = $fileSystemIo ?: ObjectManager::getInstance()->get(FileSystemIo::class);

        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function lockFileExists()
    {
        return (bool)count($this->getLockFiles());
    }

    /**
     * @return array
     */
    public function getLockFiles()
    {
        if (null === $this->lockFiles) {
            $this->lockFiles = [];

            try {
                $this->lockFiles = Glob::glob(
                    $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/' . static::SUBPROCESS_LOCK_FILE
                );
            } catch (FileSystemException $exception) {
                $this->_logger->error($exception->getMessage());
            }
        }

        return $this->lockFiles;
    }

    /**
     * @param $filePath
     *
     * @return false|string
     */
    public function getFileTime($filePath)
    {
        $fileTime = '';
        try {
            $fileInfo = $this->getFileInfo($filePath);
            $this->fileSystemIo->cd($fileInfo['dirname']);
            $files = $this->fileSystemIo->ls();

            $lockFiles = array_filter($files, function ($file) {
                return $file['text'] === static::SUBPROCESS_LOCK_FILE;
            });
            $keys = array_keys($lockFiles);
            $fileTime = isset($lockFiles[$keys[0]]['mod_date']) ? $lockFiles[$keys[0]]['mod_date'] : '';
        } catch (\Exception $exception) {
            $this->_logger->error($exception->getMessage());
        }

        return $fileTime;
    }

    /**
     * @param $filePath
     *
     * @return string
     */
    public function getLockUrl($filePath)
    {
        $params = [];
        $fileInfo = $this->getFileInfo($filePath);
        $params['filename'] = $fileInfo['basename'];

        return $this->_urlBuilder->getUrl(static::KLEVU_SEARCH_SYNC_CLEARLOCK_PATH, $params);
    }

    /**
     * @return string
     */
    public function getCronHelpArticleLink()
    {
        return HelpArticleService::HELP_ARTICLE_LINK_CRON_JOB;
    }

    /**
     * @param $filePath
     *
     * @return mixed
     */
    private function getFileInfo($filePath)
    {
        if (!$this->fileInfo) {
            $this->fileInfo = $this->fileSystemIo->getPathInfo($filePath);
        }

        return $this->fileInfo;
    }
}
