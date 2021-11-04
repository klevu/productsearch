<?php

namespace Klevu\Search\Model\Message;

use Klevu\Search\Helper\Config as KlevuHelperConfig;
use Klevu\Search\Model\System\Config\Source\NotificationOptions;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

/**
 * Class ObjectVsCollectionMessage
 * @package Klevu\Search\Model\Message
 */
class ObjectVsCollectionMessage implements MessageInterface
{
    const MESSAGE_ID = 'KLEVU_OBJECT_VS_COLLECTION';

    /**
     * ObjectMethodUse constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param KlevuHelperConfig $searchHelperConfig
     */
    public function __construct(
        UrlInterface $urlBuilder,
        KlevuHelperConfig $searchHelperConfig
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->_searchHelperConfig = $searchHelperConfig;
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
        //Only to show if option (At the top of every Magento Admin page) selected and not enabled collection method
        if (NotificationOptions::LOCK_WARNING_EVERY_ADMIN_PAGE === $this->_searchHelperConfig->getObjMethodNotificationOption()
            && !$this->_searchHelperConfig->isCollectionMethodEnabled()) {
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
        $url = 'https://help.klevu.com/support/solutions/articles/5000871455-sync-data-using-collection-method';
        $configURL = $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/klevu_search');

        $message = __('Klevu Search is currently using Object method, which may be impacting your data sync performance.');
        $message .= ' ' . __('Please read <a href="%1" target="_blank">Object vs Collection Method</a> for more information.', $url);
        $message .= ' ' . __('This warning can be disabled via <a href="%1#row_klevu_search_notification_object_vs_collection">Notification Settings</a>.', $configURL);
        return $message;

    }

    public function getSeverity()
    {
        return MessageInterface::SEVERITY_CRITICAL;
    }
}


