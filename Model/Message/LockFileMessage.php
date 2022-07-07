<?php

namespace Klevu\Search\Model\Message;

use Klevu\Search\Helper\Backend as Klevu_HelperBackend;
use Klevu\Search\Helper\Config as KlevuHelperConfig;
use Klevu\Search\Helper\Data as KlevuHelperData;
use Klevu\Search\Model\System\Config\Source\NotificationOptions;
use Klevu\Search\Service\Account\HelpArticleService;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

class LockFileMessage implements MessageInterface
{
    const MESSAGE_ID = 'KLEVU_LOCK_FILE';
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var KlevuHelperConfig
     */
    protected $_searchHelperConfig;
    /**
     * @var KlevuHelperData
     */
    protected $_searchHelperData;
    /**
     * @var Klevu_HelperBackend
     */
    protected $_searchHelperBackend;
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    public function __construct(
        UrlInterface $urlBuilder,
        DirectoryList $directoryList,
        KlevuHelperConfig $searchHelperConfig,
        KlevuHelperData $searchHelperData,
        Klevu_HelperBackend $searchHelperBackend,
        Filesystem $fileSystem
    ) {
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
     * @return bool
     */
    public function isDisplayed()
    {
        //Not showing if Disabled or Klevu Config Selected
        $option = $this->_searchHelperConfig->getLockFileNotificationOption();
        if ($option !== NotificationOptions::LOCK_WARNING_EVERY_ADMIN_PAGE) {
            return false;
        }

        return $this->_searchHelperBackend->isOutdatedLockFilesExist();
    }

    /**
     * Retrieve message text
     *
     * @return string
     */
    public function getText()
    {
        $return = __(
            'Klevu Search has detected one or more outdated Lock Files, data sync may not be working correctly.'
        );
        $return .= ' ' . __(
            'Please read about %1Magento Lock Files%2 for more information.',
            '<a href="' . HelpArticleService::HELP_ARTICLE_LINK_NOTIFICATION . '" target="_blank">',
            '</a>'
        );
        $return .= ' ' . __(
            'This warning can be disabled via %1Notification Settings%2',
            '<a href="' . HelpArticleService::HELP_ARTICLE_LINK_LOCK_FILES . '" target="_blank">',
            '</a>'
        );

        return $return;
    }

    /**
     * @return int
     */
    public function getSeverity()
    {
        return MessageInterface::SEVERITY_CRITICAL;
    }
}
