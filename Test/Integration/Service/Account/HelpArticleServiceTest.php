<?php

namespace Klevu\Search\Test\Integration\Service\Account;

use Klevu\Search\Api\Service\Account\HelpArticleServiceInterface;
use Klevu\Search\Service\Account\HelpArticleService;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class HelpArticleServiceTest extends TestCase
{
    public function testCanBeInstantiated()
    {
        $helpArticleService = ObjectManager::getInstance()->get(HelpArticleServiceInterface::class);

        $this->assertInstanceOf(HelpArticleService::class, $helpArticleService);

        $this->assertSame(HelpArticleService::HELP_ARTICLE_LINK_CRON_JOB, $helpArticleService->getCronJobArticle());
        $this->assertSame(HelpArticleService::HELP_ARTICLE_LINK_AREA_CODE, $helpArticleService->getAreaCodeArticle());
        $this->assertSame(HelpArticleService::HELP_ARTICLE_LINK_LOCK_FILES, $helpArticleService->getLockFileArticle());
        $this->assertSame(HelpArticleService::HELP_ARTICLE_LINK_OBJECT_VS_COLLECTION, $helpArticleService->getObjectVsCollectionArticle());
        $this->assertSame(HelpArticleService::HELP_ARTICLE_LINK_ORDERS_FROM_SAME_IP, $helpArticleService->getOrdersFromSameIpArticle());
        $this->assertSame(HelpArticleService::HELP_ARTICLE_LINK_INTEGRATION_STEPS, $helpArticleService->getIntegrationStepsArticle());
        $this->assertSame(HelpArticleService::HELP_ARTICLE_LINK_MIGRATING_STAGING_LIVE, $helpArticleService->getMigratingStagingToLiveArticle());
        $this->assertSame(HelpArticleService::HELP_ARTICLE_LINK_UPGRADE, $helpArticleService->getUpgradeArticle());
        $this->assertSame(HelpArticleService::HELP_ARTICLE_LINK_CRON_SETUP, $helpArticleService->getCronSetupArticle());
        $this->assertSame(HelpArticleService::HELP_ARTICLE_LINK_NOTIFICATION, $helpArticleService->getNotificationArticle());
    }
}
