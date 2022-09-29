<?php

namespace Klevu\Search\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class InstallNotificationData implements DataPatchInterface
{
    const CRON_SETUP_LINK = 'https://help.klevu.com/support/solutions/articles/5000871452-setup-external-cron-job';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * PatchInitial constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @return string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return $this|InstallNotificationData
     */
    public function apply()
    {
        $notificationTable = $this->moduleDataSetup->getTable('klevu_notification');

        $select = $this->moduleDataSetup->getConnection()->select()
            ->from($notificationTable)
            ->where('type = ?', 'cron_check');
        $notification = $this->moduleDataSetup->getConnection()->fetchAll($select);

        if (!$notification) {
            $this->moduleDataSetup->getConnection()->insert(
                $notificationTable,
                [
                    'type' => 'cron_check',
                    'message' => $this->getMessage()
                ]
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    private function getMessage(): string
    {
        $message = __('Klevu Search relies on cron for normal operations.');
        $message .= __('Please check that you have Magento cron set up correctly.');
        $message .= __(
            'You can find instructions on how to set up Magento Cron %1 here %2.',
            '<a target="_blank" href="' . static::CRON_SETUP_LINK . '">',
            '</a>'
        );

        return $message;
    }
}
