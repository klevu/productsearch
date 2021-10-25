<?php

namespace Klevu\Search\Model\Message;

use Klevu\Search\Helper\Backend as Klevu_HelperBackend;
use Klevu\Search\Helper\Config as KlevuHelperConfig;
use Klevu\Search\Helper\Data as KlevuHelperData;
use Klevu\Search\Model\System\Config\Source\NotificationOptions;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

/**
 * Class LockFileMessage
 * @package Klevu\Search\Model\Message
 */
class LockFileMessage implements MessageInterface
{
    const MESSAGE_ID = 'KLEVU_LOCK_FILE';

    /**
     * LockFileMessages constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param DirectoryList $directoryList
     * @param KlevuHelperConfig $searchHelperConfig
     * @param Filesystem $fileSystem
     */
    public function __construct(
        UrlInterface $urlBuilder,
        DirectoryList $directoryList,
        KlevuHelperConfig $searchHelperConfig,
        KlevuHelperData $searchHelperData,
        Klevu_HelperBackend $searchHelperBackend,
        Filesystem $fileSystem
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->directoryList = $directoryList;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_searchHelperData = $searchHelperData;
        $this->_searchHelperBackend = $searchHelperBackend;
        $this->fileSystem = $fileSystem;
    }

    /**
     * @return string
     */
    public function getIdentity()
    {
        return self::MESSAGE_ID;
    }

    /**
     * @return bool|void
     */
    public function isDisplayed()
    {
        //Not showing if Disabled or Klevu Config Selected
        $option = (int)$this->_searchHelperConfig->getLockFileNotificationOption();
        if ($option !== NotificationOptions::LOCK_WARNING_EVERY_ADMIN_PAGE) {
            return false;
        }
        $flag = $this->_searchHelperBackend->isOutdatedLockFilesExist();
        if ($flag) {
            return true;
        }
        return false;
    }

    /**
     * Retrieve message text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        $url = $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/klevu_search');
        //@codingStandardsIgnoreStart
        return __(
            'Klevu Search has detected one or more outdated Lock Files, data sync may not be working correctly.
            Please read about <a href="%2" target="_blank">Magento Lock Files</a> for more information.
            This warning can be disabled via <a href="%1#row_klevu_search_notification_lock_file">Notification Settings</a>',
            $url,
            'https://help.klevu.com/support/solutions/articles/5000871506-lock-files-for-data-sync/'
        );
        //@codingStandardsIgnoreEnd
    }

    public function getSeverity()
    {
        return MessageInterface::SEVERITY_CRITICAL;
    }
}

