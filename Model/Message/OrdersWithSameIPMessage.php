<?php

namespace Klevu\Search\Model\Message;

use Klevu\Search\Helper\Config as KlevuHelperConfig;
use Klevu\Search\Model\Order\OrdersWithSameIPCollection;
use Klevu\Search\Model\System\Config\Source\NotificationOptions;
use Klevu\Search\Service\Account\HelpArticleService;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

class OrdersWithSameIPMessage implements MessageInterface
{
    const MESSAGE_ID = 'KLEVU_ORDERS_WITH_SAME_IPS';

    /**
     * @var KlevuHelperConfig
     */
    private $searchHelperConfig;
    /**
     * @var OrdersWithSameIPCollection
     */
    private $ordersWithSameIPCollection;

    public function __construct(
        UrlInterface $urlBuilder,
        KlevuHelperConfig $searchHelperConfig,
        OrdersWithSameIPCollection $ordersWithSameIPCollection
    ) {
        $this->searchHelperConfig = $searchHelperConfig;
        $this->ordersWithSameIPCollection = $ordersWithSameIPCollection;
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
        return NotificationOptions::LOCK_WARNING_EVERY_ADMIN_PAGE === $this->searchHelperConfig->isOrdersWithSameIPNotificationOptionEnabled()
            && $this->ordersWithSameIPCollection->execute();
    }

    /**
     * Retrieve message text
     *
     * @return string
     */
    public function getText()
    {
        $return = __(
            'Klevu has detected many checkout orders originating from the same IP address causing inaccuracies in Klevu sales analytics.'
        );
        $return .= ' ' . __(
            'Please %1read this article%2 for more information on how to resolve this issue.',
            '<a href="' . HelpArticleService::HELP_ARTICLE_LINK_ORDERS_FROM_SAME_IP . '" target="_blank">',
            '</a>'
        );
        $return .= ' ' . __(
            'This warning can be disabled via %1Notification Settings%2',
            '<a href="' . HelpArticleService::HELP_ARTICLE_LINK_NOTIFICATION . '" target="_blank">',
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
