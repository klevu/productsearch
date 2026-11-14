<?php

namespace Klevu\Search\Test\Unit\Model\Api;

use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Api\Response;
use Magento\AdminNotification\Model\Inbox;
use Magento\AdminNotification\Model\InboxFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class ResponseBadRequestTest extends TestCase
{
    public function testBadRequestWithoutStoreMatchedByApiKeyDoesNotFail()
    {
        $searchHelper = $this->getMockSearchHelper(null);
        $response = new Response(
            $searchHelper,
            $this->getMockInboxFactory('Product Sync failed for this store:')
        );

        $response->setRawResponse($this->getMockHttpResponse(400));

        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('Product Sync failed for this store:', $response->getMessage());
    }

    public function testBadRequestWithStoreMatchedByApiKeyNamesWebsiteAndStore()
    {
        $website = $this->createStub(Website::class);
        $website->method('getName')->willReturn('Main Website');
        $store = $this->createStub(Store::class);
        $store->method('getWebsite')->willReturn($website);
        $store->method('getName')->willReturn('Default Store View');

        $response = new Response(
            $this->getMockSearchHelper($store),
            $this->getMockInboxFactory('Product Sync failed for Main Website (Default Store View):')
        );

        $response->setRawResponse($this->getMockHttpResponse(400));

        $this->assertStringContainsString(
            'Product Sync failed for Main Website (Default Store View):',
            $response->getMessage()
        );
    }

    /**
     * @param StoreInterface|null $store
     *
     * @return SearchHelper|Stub
     */
    private function getMockSearchHelper($store)
    {
        $helper = $this->createStub(SearchHelper::class);
        $helper->method('storeFromScopeId')->willReturn($store);
        $helper->method('getBaseDomain')->willReturn('example.com');
        $helper->method('getJsApiKey')->willReturn('klevu-123');

        return $helper;
    }

    /**
     * @param string $expectedDescriptionStart
     *
     * @return InboxFactory|Stub
     */
    private function getMockInboxFactory($expectedDescriptionStart)
    {
        $inbox = $this->createMock(Inbox::class);
        $inbox->expects($this->once())
            ->method('parse')
            ->with($this->callback(function ($data) use ($expectedDescriptionStart) {
                return isset($data[0]['description'])
                    && strpos($data[0]['description'], $expectedDescriptionStart) === 0;
            }));
        $factory = $this->createStub(InboxFactory::class);
        $factory->method('create')->willReturn($inbox);

        return $factory;
    }

    /**
     * @param int $statusCode
     *
     * @return object
     */
    private function getMockHttpResponse($statusCode)
    {
        return new class ($statusCode) {
            private $statusCode;

            public function __construct($statusCode)
            {
                $this->statusCode = $statusCode;
            }

            public function isSuccess()
            {
                return false;
            }

            public function getStatusCode()
            {
                return $this->statusCode;
            }

            public function toString()
            {
                return 'HTTP/1.1 ' . $this->statusCode;
            }
        };
    }
}
