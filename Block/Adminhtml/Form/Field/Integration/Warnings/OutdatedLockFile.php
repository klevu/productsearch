<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration\Warnings;

use Klevu\Search\Helper\Backend as BackendHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\System\Config\Source\NotificationOptions;
use Klevu\Search\Service\Account\HelpArticleService;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;

class OutdatedLockFile extends Template
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var BackendHelper
     */
    private $backendHelper;

    public function __construct(
        Context $context,
        array $data = [],
        ConfigHelper $configHelper = null,
        BackendHelper $backendHelper = null
    ) {
        $this->configHelper = $configHelper ?: ObjectManager::getInstance()->get(ConfigHelper::class);
        $this->backendHelper = $backendHelper ?: ObjectManager::getInstance()->get(BackendHelper::class);

        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getLockFilesArticleLink()
    {
        return HelpArticleService::HELP_ARTICLE_LINK_LOCK_FILES;
    }

    /**
     * @return string
     */
    public function getNotificationArticleLink()
    {
        return HelpArticleService::HELP_ARTICLE_LINK_NOTIFICATION;
    }

    /**
     * @return bool
     */
    public function isLockFileOutdated()
    {
        if ($this->configHelper->getLockFileNotificationOption() === NotificationOptions::LOCK_WARNING_DISABLE) {
            return false;
        }

        return $this->backendHelper->isOutdatedLockFilesExist();
    }
}
