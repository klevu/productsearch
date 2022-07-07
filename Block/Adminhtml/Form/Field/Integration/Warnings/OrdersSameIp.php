<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration\Warnings;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Order\OrdersWithSameIPCollection;
use Klevu\Search\Model\System\Config\Source\NotificationOptions;
use Klevu\Search\Service\Account\HelpArticleService;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;

class OrdersSameIp extends Template
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var OrdersWithSameIPCollection
     */
    private $ordersWithSameIPCollection;

    public function __construct(
        Context $context,
        array $data = [],
        ConfigHelper $configHelper = null,
        OrdersWithSameIPCollection $ordersWithSameIPCollection = null
    ) {
        $this->configHelper = $configHelper ?: ObjectManager::getInstance()->get(ConfigHelper::class);
        $this->ordersWithSameIPCollection = $ordersWithSameIPCollection ?: ObjectManager::getInstance()->get(OrdersWithSameIPCollection::class);

        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function hasMultipleOrdersWithSameIp()
    {
        if ($this->configHelper->getLockFileNotificationOption() === NotificationOptions::LOCK_WARNING_DISABLE) {
            return false;
        }

        return $this->ordersWithSameIPCollection->execute();
    }

    /**
     * @return string
     */
    public function getOrderFromSameIpArticleLink()
    {
        return HelpArticleService::HELP_ARTICLE_LINK_ORDERS_FROM_SAME_IP;
    }

    /**
     * @return string
     */
    public function getNotificationArticleLink()
    {
        return HelpArticleService::HELP_ARTICLE_LINK_NOTIFICATION;
    }
}
