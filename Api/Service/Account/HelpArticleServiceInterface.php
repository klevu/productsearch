<?php

namespace Klevu\Search\Api\Service\Account;

interface HelpArticleServiceInterface
{
    /**
     * @return string
     */
    public function getCronJobArticle();

    /**
     * @return string
     */
    public function getAreaCodeArticle();

    /**
     * @return string
     */
    public function getLockFileArticle();

    /**
     * @return string
     */
    public function getObjectVsCollectionArticle();

    /**
     * @return string
     */
    public function getOrdersFromSameIpArticle();

    /**
     * @return string
     */
    public function getIntegrationStepsArticle();

    /**
     * @return string
     */
    public function getMigratingStagingToLiveArticle();

    /**
     * @return string
     */
    public function getUpgradeArticle();

    /**
     * @return string
     */
    public function getCronSetupArticle();

    /**
     * @return string
     */
    public function getNotificationArticle();
}
