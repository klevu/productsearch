<?php

namespace Klevu\Search\Model\Message;

use Klevu\Search\Helper\Config as KlevuHelperConfig;
use Klevu\Search\Model\System\Config\Source\NotificationOptions;
use Klevu\Search\Service\Account\HelpArticleService;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

class ObjectVsCollectionMessage implements MessageInterface
{
    const MESSAGE_ID = 'KLEVU_OBJECT_VS_COLLECTION';
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var KlevuHelperConfig
     */
    protected $_searchHelperConfig;

    /**
     * ObjectMethodUse constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param KlevuHelperConfig $searchHelperConfig
     */
    public function __construct(
        UrlInterface $urlBuilder,
        KlevuHelperConfig $searchHelperConfig
    ) {
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
     * @return bool
     */
    public function isDisplayed()
    {
        //Only to show if option (At the top of every Magento Admin page) selected and not enabled collection method
        return NotificationOptions::LOCK_WARNING_EVERY_ADMIN_PAGE === $this->_searchHelperConfig->getObjMethodNotificationOption()
            && !$this->_searchHelperConfig->isCollectionMethodEnabled();
    }

    /**
     * Retrieve message text
     *
     * @return string
     */
    public function getText()
    {
        $message = __(
            'Klevu Search is currently using Object method, which may be impacting your data sync performance.'
        );
        $message .= ' ' . __(
            'Please read %1Object vs Collection Method%2 for more information.',
            '<a href="' . HelpArticleService::HELP_ARTICLE_LINK_OBJECT_VS_COLLECTION . '" target="_blank">',
            '</a>'
        );
        $message .= ' ' . __(
            'This warning can be disabled via %1Notification Settings%2.',
            '<a href="' . HelpArticleService::HELP_ARTICLE_LINK_NOTIFICATION . '">',
            '</a>'
        );

        return $message;
    }

    /**
     * @return int
     */
    public function getSeverity()
    {
        return MessageInterface::SEVERITY_CRITICAL;
    }
}
