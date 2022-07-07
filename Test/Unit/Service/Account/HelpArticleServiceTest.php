<?php

namespace Klevu\Search\Test\Unit\Service\Account;

use Klevu\Search\Api\Service\Account\HelpArticleServiceInterface;
use Klevu\Search\Service\Account\HelpArticleService;
use PHPUnit\Framework\TestCase;

class HelpArticleServiceTest extends TestCase
{
    public function testItImplementsHelpArticleServiceInterface()
    {
        $observer = $this->instantiateHelpArticleService();
        $this->assertInstanceOf(HelpArticleServiceInterface::class, $observer);
    }

    /**
     * @return HelpArticleService
     */
    private function instantiateHelpArticleService()
    {
        return new HelpArticleService();
    }
}
