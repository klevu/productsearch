<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration\Instructions;

use Klevu\Search\Api\Service\Account\GetKmcUrlServiceInterface;
use Klevu\Search\Service\Account\HelpArticleService;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class QuickLinks extends Template
{
    /**
     * @var GetKmcUrlServiceInterface
     */
    private $getKmcUrlService;

    public function __construct(
        Context $context,
        array $data = [],
        GetKmcUrlServiceInterface $getKmcUrlService = null
    ) {
        parent::__construct($context, $data);
        $this->getKmcUrlService = $getKmcUrlService ?: ObjectManager::getInstance()->get(GetKmcUrlServiceInterface::class);
    }

    /**
     * @return string
     */
    public function getIntegrationArticleLink()
    {
        return HelpArticleService::HELP_ARTICLE_LINK_INTEGRATION_STEPS;
    }

    /**
     * @return string
     */
    public function getMigratingFromStagingToLiveArticleLink()
    {
        return HelpArticleService::HELP_ARTICLE_LINK_MIGRATING_STAGING_LIVE;
    }

    /**
     * @return string
     */
    public function getKlevuMerchantCenterLink()
    {
        return $this->getKmcUrlService->execute();
    }
}
