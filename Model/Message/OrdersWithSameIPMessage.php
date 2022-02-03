<?php

namespace Klevu\Search\Model\Message;

use Klevu\Search\Helper\Config as KlevuHelperConfig;
use Klevu\Search\Model\Order\OrdersWithSameIPCollection;
use Klevu\Search\Model\System\Config\Source\NotificationOptions;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

/**
 * Class OrdersWithSameIPMessage
 * @package Klevu\Search\Model\Message
 */
class OrdersWithSameIPMessage implements MessageInterface
{
    const MESSAGE_ID = 'KLEVU_ORDERS_WITH_SAME_IPS';
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var KlevuHelperConfig
     */
    private $searchHelperConfig;
    /**
     * @var OrdersWithSameIPCollection
     */
    private $ordersWithSameIPCollection;

    /**
     * ObjectMethodUse constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param KlevuHelperConfig $searchHelperConfig
     * @param SalesCollection $salesCollection
     */
    public function __construct(
        UrlInterface      $urlBuilder,
        KlevuHelperConfig $searchHelperConfig,
        OrdersWithSameIPCollection   $ordersWithSameIPCollection
    )
    {
        $this->urlBuilder = $urlBuilder;
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
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        $url = $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/klevu_search');
        //@codingStandardsIgnoreStart
        return __(
            'Klevu has detected many checkout orders originating from the same IP address causing inaccuracies in Klevu sales analytics.
            Please <a href="%2" target="_blank">read this article</a> for more information on how to resolve this issue.
            This warning can be disabled via <a href="%1#row_klevu_search_notification_orders_with_same_ip">Notification Settings</a>',
            $url,
            'https://help.klevu.com/support/solutions/articles/5000874087-multiple-orders-received-from-the-same-ip-address'
        );
        //@codingStandardsIgnoreEnd
    }

    public function getSeverity()
    {
        return MessageInterface::SEVERITY_CRITICAL;
    }
}
