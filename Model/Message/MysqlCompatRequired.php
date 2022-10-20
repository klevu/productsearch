<?php

namespace Klevu\Search\Model\Message;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Module\ManagerFactory as ModuleManagerFactory;
use Magento\Framework\Notification\MessageInterface;

class MysqlCompatRequired implements MessageInterface
{
    const MESSAGE_ID = 'KLEVU_MYSQL_COMPAT_MODULE_REQUIRED';
    const SHOW_BEFORE_VERSION = '2.4.0';

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;
    /**
     * @var ModuleManagerFactory
     */
    private $moduleManagerFactory;

    /**
     * @param ProductMetadataInterface $productMetadata
     * @param ModuleManagerFactory $moduleManagerFactory
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        ModuleManagerFactory $moduleManagerFactory
    ) {
        $this->productMetadata = $productMetadata;
        $this->moduleManagerFactory = $moduleManagerFactory;
    }

    /**
     * @return int
     */
    public function getSeverity()
    {
        return MessageInterface::SEVERITY_MAJOR;
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
        /** @var ModuleManager $moduleManager */
        $moduleManager = $this->moduleManagerFactory->create();
        $moduleEnabled = $moduleManager->isEnabled('Klevu_MysqlCompat');

        return !$moduleEnabled &&
            version_compare($this->productMetadata->getVersion(), self::SHOW_BEFORE_VERSION) < 0;
    }

    /**
     * @return string
     */
    public function getText()
    {
        $return = __('The module Klevu_MysqlCompat is either not installed or not enabled.') . ' ';
        $return .= __('This module is required for Klevu to function correctly in Magento 2.3.') . '<br />';
        $return .= __('It can be installed via composer with "composer require klevu/module-mysqlcompat"');

        return $return;
    }
}
