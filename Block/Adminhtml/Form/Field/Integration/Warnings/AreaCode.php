<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration\Warnings;

use Klevu\Search\Service\Account\HelpArticleService;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Glob;

class AreaCode extends Template
{
    const AREA_CODE_LOCK_FILE = 'klevu_areacode*.lock';

    /**
     * @var DirectoryList
     */
    private $directoryList;
    /**
     * @var array
     */
    private $lockFiles = [];

    public function __construct(
        Context $context,
        array $data = [],
        DirectoryList $directoryList = null
    ) {
        $this->directoryList = $directoryList ?: ObjectManager::getInstance()->get(DirectoryList::class);

        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getAreaCodeArticleLink()
    {
        return HelpArticleService::HELP_ARTICLE_LINK_AREA_CODE;
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
    private function getLockFiles()
    {
        if (count($this->lockFiles)) {
            return $this->lockFiles;
        }
        try {
            $this->lockFiles = Glob::glob(
                $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/' . static::AREA_CODE_LOCK_FILE
            );
        } catch (FileSystemException $exception) {
            $this->_logger->error($exception->getMessage());
        }

        return $this->lockFiles;
    }
}
