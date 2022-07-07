<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration\Warnings;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\System\Config\Source\NotificationOptions;
use Klevu\Search\Service\Account\HelpArticleService;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;

class ObjectVsCollection extends Template
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    public function __construct(
        Context $context,
        array $data = [],
        ConfigHelper $configHelper = null
    ) {
        $this->configHelper = $configHelper ?: ObjectManager::getInstance()->get(ConfigHelper::class);

        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isObjectMethodSet()
    {
        if ($this->configHelper->getLockFileNotificationOption() === NotificationOptions::LOCK_WARNING_DISABLE) {
            return false;
        }

        return !$this->configHelper->isCollectionMethodEnabled();
    }

    /**
     * @return string
     */
    public function getNotificationArticleLink()
    {
        return HelpArticleService::HELP_ARTICLE_LINK_NOTIFICATION;
    }

    /**
     * @return string
     */
    public function getObjectVsCollectionArticleLink()
    {
        return HelpArticleService::HELP_ARTICLE_LINK_OBJECT_VS_COLLECTION;
    }
}
