<?php

namespace Klevu\Search\Test\Integration\Service\Account;

use Klevu\Search\Api\Service\Account\Model\AccountDetailsInterface;
use Klevu\Search\Api\Service\Account\UpdateEndpointsInterface;
use Klevu\Search\Service\Account\Model\AccountDetails;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriter;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class UpdateEndpointsServiceTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testEndpointUrlsAreUpdated()
    {
        $this->setUpPhp5();

        $mockScopeConfigWriter = $this->getMockBuilder(ScopeConfigWriter::class)->disableOriginalConstructor()->getMock();
        $saveCount = 9; // once for each URL, once for legacy cloud search URL, and once for last sync date
        $mockScopeConfigWriter->expects($this->exactly($saveCount))->method('save');
        $mockScopeConfigWriter->expects($this->never())->method('delete');

        $accountDetails = $this->objectManager->create(AccountDetailsInterface::class);
        $accountDetails->setEmail('user@klevu.com');
        $accountDetails->setActive(true);
        $accountDetails->setCompany('Klevu');
        $accountDetails->setPlatform(AccountDetails::PLATFORM_MAGENTO);
        $accountDetails->setAnalyticsUrl('stats.klevu.com');
        $accountDetails->setCatNavUrl('cn26.ksearchnet.com');
        $accountDetails->setCatNavTrackingUrl('cnstats.ksearchnet.com');
        $accountDetails->setIndexingUrl('indexing-qa.ksearchnet.com');
        $accountDetails->setJsUrl('js.klevu.com');
        $accountDetails->setSearchUrl('eucs26v2.ksearchnet.com');
        $accountDetails->setTiersUrl('tiers.klevu.com');

        $updateEndpoints = $this->objectManager->create(UpdateEndpointsInterface::class, [
            'scopeConfigWriter' => $mockScopeConfigWriter
        ]);
        $storeId = 1;

        $updateEndpoints->execute($accountDetails, $storeId);
    }

    public function testEndpointUrlsAreRemoved()
    {
        $this->setUpPhp5();

        $mockScopeConfigWriter = $this->getMockBuilder(ScopeConfigWriter::class)->disableOriginalConstructor()->getMock();
        $mockScopeConfigWriter->expects($this->once())->method('save');
        $deleteCount = 8; // once for each endpoint, including additional for legacy cloud search URL
        $mockScopeConfigWriter->expects($this->exactly($deleteCount))->method('delete');

        $accountDetails = $this->objectManager->create(AccountDetailsInterface::class);

        $updateEndpoints = $this->objectManager->create(UpdateEndpointsInterface::class, [
            'scopeConfigWriter' => $mockScopeConfigWriter
        ]);
        $storeId = 1;

        $updateEndpoints->execute($accountDetails, $storeId);
    }

    /**
     * @return void
     * @todo remove once support for PHP 5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }
}
