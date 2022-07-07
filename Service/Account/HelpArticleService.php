<?php

namespace Klevu\Search\Service\Account;

use Klevu\Search\Api\Service\Account\HelpArticleServiceInterface;

class HelpArticleService implements HelpArticleServiceInterface
{
    const HELP_ARTICLE_LINK_CRON_JOB = 'https://help.klevu.com/support/solutions/articles/5000871452-setup-external-cron-job';
    const HELP_ARTICLE_LINK_AREA_CODE = 'https://help.klevu.com/support/solutions/articles/5000871360-area-code-is-already-set';
    const HELP_ARTICLE_LINK_LOCK_FILES = 'https://help.klevu.com/support/solutions/articles/5000871506-lock-files-for-data-sync';
    const HELP_ARTICLE_LINK_OBJECT_VS_COLLECTION = 'https://help.klevu.com/support/solutions/articles/5000871455-sync-data-using-collection-method';
    const HELP_ARTICLE_LINK_ORDERS_FROM_SAME_IP = 'https://help.klevu.com/support/solutions/articles/5000874087-multiple-orders-received-from-the-same-ip-address';
    const HELP_ARTICLE_LINK_INTEGRATION_STEPS = 'https://help.klevu.com/support/solutions/articles/5000871252-integration-steps-for-magento-2';
    const HELP_ARTICLE_LINK_MIGRATING_STAGING_LIVE = 'https://help.klevu.com/support/solutions/folders/5000308570';
    const HELP_ARTICLE_LINK_UPGRADE = 'https://help.klevu.com/support/solutions/articles/5000871369-how-to-upgrade-my-plan-';
    const HELP_ARTICLE_LINK_CRON_SETUP = 'https://help.klevu.com/support/solutions/articles/5000871452-setup-external-cron-job';
    const HELP_ARTICLE_LINK_NOTIFICATION = 'https://help.klevu.com/support/solutions/articles/5000876105-developer-and-notification-setting';

    /**
     * @return string
     */
    public function getCronJobArticle()
    {
        return static::HELP_ARTICLE_LINK_CRON_JOB;
    }

    /**
     * @return string
     */
    public function getAreaCodeArticle()
    {
        return static::HELP_ARTICLE_LINK_AREA_CODE;
    }

    /**
     * @return string
     */
    public function getLockFileArticle()
    {
        return static::HELP_ARTICLE_LINK_LOCK_FILES;
    }

    /**
     * @return string
     */
    public function getObjectVsCollectionArticle()
    {
        return static::HELP_ARTICLE_LINK_OBJECT_VS_COLLECTION;
    }

    /**
     * @return string
     */
    public function getOrdersFromSameIpArticle()
    {
        return static::HELP_ARTICLE_LINK_ORDERS_FROM_SAME_IP;
    }

    /**
     * @return string
     */
    public function getIntegrationStepsArticle()
    {
        return static::HELP_ARTICLE_LINK_INTEGRATION_STEPS;
    }

    /**
     * @return string
     */
    public function getMigratingStagingToLiveArticle()
    {
        return static::HELP_ARTICLE_LINK_MIGRATING_STAGING_LIVE;
    }

    /**
     * @return string
     */
    public function getUpgradeArticle()
    {
        return static::HELP_ARTICLE_LINK_UPGRADE;
    }

    /**
     * @return string
     */
    public function getCronSetupArticle()
    {
        return static::HELP_ARTICLE_LINK_CRON_SETUP;
    }

    /**
     * @return string
     */
    public function getNotificationArticle()
    {
        return static::HELP_ARTICLE_LINK_NOTIFICATION;
    }
}
