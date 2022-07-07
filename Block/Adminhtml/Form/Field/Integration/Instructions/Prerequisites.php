<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration\Instructions;

use Klevu\Search\Service\Account\HelpArticleService;
use Magento\Framework\View\Element\Template;

class Prerequisites extends Template
{
    /**
     * @return string
     */
    public function getCronSetupArticleLink()
    {
        return HelpArticleService::HELP_ARTICLE_LINK_CRON_SETUP;
    }
}
