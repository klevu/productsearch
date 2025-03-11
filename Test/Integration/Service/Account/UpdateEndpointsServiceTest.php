<?php

namespace Klevu\Search\Test\Integration\Service\Account;

use Klevu\Registry\Api\ConfigRegistryInterface;
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

    /**
     * @magentoConfigFixture default_store general/single_store_mode/enabled 0
     * @magentoConfigFixture default/klevu_integration/integration/status 0
     */
    public function testEndpointUrlsAreUpdatedInMultisiteMode()
    {
        $this->setUpPhp5();

        $mockScopeConfigWriter = $this->getMockBuilder(ScopeConfigWriter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $saveCount = 9; // once for each URL, once for legacy cloud search URL, and once for last sync date
        $mockScopeConfigWriter->expects($this->exactly($saveCount))
            ->method('save')
            ->withConsecutive(
                ['klevu_search/general/analytics_url', 'stats.klevu.com', 'stores', 1],
                ['klevu_search/general/category_navigation_url', 'cn26.ksearchnet.com', 'stores', 1],
                ['klevu_search/general/category_navigation_tracking_url', 'cnstats.ksearchnet.com', 'stores', 1],
                ['klevu_search/general/rest_hostname', 'indexing.ksearchnet.com', 'stores', 1],
                ['klevu_search/general/js_url', 'js.klevu.com', 'stores', 1],
                ['klevu_search/general/cloud_search_url', 'eucs26v2.ksearchnet.com', 'stores', 1],
                ['klevu_search/general/cloud_search_v2_url', 'eucs26v2.ksearchnet.com', 'stores', 1],
                ['klevu_search/general/tiers_url', 'tiers.klevu.com', 'stores', 1]
            );
        $mockScopeConfigWriter->expects($this->never())->method('delete');

        $accountDetails = $this->objectManager->create(AccountDetailsInterface::class);
        $accountDetails->setEmail('user@klevu.com');
        $accountDetails->setActive(true);
        $accountDetails->setCompany('Klevu');
        $accountDetails->setPlatform(AccountDetails::PLATFORM_MAGENTO);
        $accountDetails->setAnalyticsUrl('stats.klevu.com');
        $accountDetails->setCatNavUrl('cn26.ksearchnet.com');
        $accountDetails->setCatNavTrackingUrl('cnstats.ksearchnet.com');
        $accountDetails->setIndexingUrl('indexing.ksearchnet.com');
        $accountDetails->setJsUrl('js.klevu.com');
        $accountDetails->setSearchUrl('eucs26v2.ksearchnet.com');
        $accountDetails->setTiersUrl('tiers.klevu.com');

        $updateEndpoints = $this->objectManager->create(UpdateEndpointsInterface::class, [
            'scopeConfigWriter' => $mockScopeConfigWriter
        ]);
        $storeId = 1;

        $updateEndpoints->execute($accountDetails, $storeId);
    }

    /**
     * @magentoConfigFixture default_store general/single_store_mode/enabled 1
     * @magentoConfigFixture default/klevu_integration/integration/status 1
     */
    public function testEndpointUrlsAreUpdatedInSingleStoreode()
    {
        $this->setUpPhp5();

        $mockScopeConfigWriter = $this->getMockBuilder(ScopeConfigWriter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $saveCount = 9; // once for each URL, once for legacy cloud search URL, and once for last sync date
        $mockScopeConfigWriter->expects($this->exactly($saveCount))
            ->method('save')
            ->withConsecutive(
                ['klevu_search/general/analytics_url', 'stats.klevu.com', 'default', 0],
                ['klevu_search/general/category_navigation_url', 'cn26.ksearchnet.com', 'default', 0],
                ['klevu_search/general/category_navigation_tracking_url', 'cnstats.ksearchnet.com', 'default', 0],
                ['klevu_search/general/rest_hostname', 'indexing.ksearchnet.com', 'default', 0],
                ['klevu_search/general/js_url', 'js.klevu.com', 'default', 0],
                ['klevu_search/general/cloud_search_url', 'eucs26v2.ksearchnet.com', 'default', 0],
                ['klevu_search/general/cloud_search_v2_url', 'eucs26v2.ksearchnet.com', 'default', 0],
                ['klevu_search/general/tiers_url', 'tiers.klevu.com', 'default', 0]
            );
        $mockScopeConfigWriter->expects($this->never())->method('delete');

        $accountDetails = $this->objectManager->create(AccountDetailsInterface::class);
        $accountDetails->setEmail('user@klevu.com');
        $accountDetails->setActive(true);
        $accountDetails->setCompany('Klevu');
        $accountDetails->setPlatform(AccountDetails::PLATFORM_MAGENTO);
        $accountDetails->setAnalyticsUrl('stats.klevu.com');
        $accountDetails->setCatNavUrl('cn26.ksearchnet.com');
        $accountDetails->setCatNavTrackingUrl('cnstats.ksearchnet.com');
        $accountDetails->setIndexingUrl('indexing.ksearchnet.com');
        $accountDetails->setJsUrl('js.klevu.com');
        $accountDetails->setSearchUrl('eucs26v2.ksearchnet.com');
        $accountDetails->setTiersUrl('tiers.klevu.com');

        $updateEndpoints = $this->objectManager->create(UpdateEndpointsInterface::class, [
            'scopeConfigWriter' => $mockScopeConfigWriter
        ]);
        $storeId = 1;

        $updateEndpoints->execute($accountDetails, $storeId);
    }

    /**
     * @magentoConfigFixture default_store general/single_store_mode/enabled 0
     * @magentoConfigFixture default/klevu_integration/integration/status 0
     */
    public function testEndpointUrlsAreRemovedInMultisiteMode()
    {
        $this->setUpPhp5();

        $mockScopeConfigWriter = $this->getMockBuilder(ScopeConfigWriter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockScopeConfigWriter->expects($this->once())
            ->method('save')
            ->with('klevu_search/features_api/last_sync_date', 0, 'stores', 1);
        $deleteCount = 8; // once for each endpoint, including additional for legacy cloud search URL
        $mockScopeConfigWriter->expects($this->exactly($deleteCount))
            ->method('delete')
            ->withConsecutive(
                ['klevu_search/general/analytics_url', 'stores', 1],
                ['klevu_search/general/category_navigation_url', 'stores', 1],
                ['klevu_search/general/category_navigation_tracking_url', 'stores', 1],
                ['klevu_search/general/rest_hostname', 'stores', 1],
                ['klevu_search/general/js_url', 'stores', 1],
                ['klevu_search/general/cloud_search_url', 'stores', 1],
                ['klevu_search/general/cloud_search_v2_url', 'stores', 1],
                ['klevu_search/general/tiers_url', 'stores', 1]
            );

        $accountDetails = $this->objectManager->create(AccountDetailsInterface::class);

        $updateEndpoints = $this->objectManager->create(UpdateEndpointsInterface::class, [
            'scopeConfigWriter' => $mockScopeConfigWriter
        ]);
        $storeId = 1;

        $updateEndpoints->execute($accountDetails, $storeId);
    }

    /**
     * @magentoConfigFixture default_store general/single_store_mode/enabled 1
     * @magentoConfigFixture default/klevu_integration/integration/status 1
     */
    public function testEndpointUrlsAreRemovedInSingleStoreMode()
    {
        $this->setUpPhp5();

        $mockScopeConfigWriter = $this->getMockBuilder(ScopeConfigWriter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockScopeConfigWriter->expects($this->once())
            ->method('save')
            ->with('klevu_search/features_api/last_sync_date', 0, 'default', 0);
        $deleteCount = 8; // once for each endpoint, including additional for legacy cloud search URL
        $mockScopeConfigWriter->expects($this->exactly($deleteCount))
            ->method('delete')
            ->withConsecutive(
                ['klevu_search/general/analytics_url', 'default', 0],
                ['klevu_search/general/category_navigation_url', 'default', 0],
                ['klevu_search/general/category_navigation_tracking_url', 'default', 0],
                ['klevu_search/general/rest_hostname', 'default', 0],
                ['klevu_search/general/js_url', 'default', 0],
                ['klevu_search/general/cloud_search_url', 'default', 0],
                ['klevu_search/general/cloud_search_v2_url', 'default', 0],
                ['klevu_search/general/tiers_url', 'default', 0]
            );

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

        /** @var ConfigRegistryInterface $configRegistry */
        $configRegistry = $this->objectManager->get(ConfigRegistryInterface::class);
        $configRegistry->reset();
    }
}
