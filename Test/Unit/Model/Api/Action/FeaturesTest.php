<?php

namespace Klevu\Search\Test\Unit\Model\Api\Action;

use Klevu\Search\Helper\Api as ApiHelper;
use Klevu\Search\Helper\Config as ConfigHelepr;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Api\Action\Features;
use Klevu\Search\Model\Api\Request;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Model\Api\Response\Invalid as InvalidApiResponse;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeaturesTest extends TestCase
{
    /**
     * @var InvalidApiResponse|MockObject
     */
    private $mockApiResponseInvalid;
    /**
     * @var Store|MockObject
     */
    private $mockStore;
    /**
     * @var ApiHelper|MockObject
     */
    private $mockApiHelper;
    /**
     * @var ConfigHelepr|MockObject
     */
    private $mockConfigHelper;
    /**
     * @var SearchHelper|MockObject
     */
    private $mockSearchHelper;
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $mockStoreManager;
    /**
     * @var Request|MockObject
     */
    private $mockRequest;
    /**
     * @var Response|MockObject
     */
    private $mockResponse;

    /**
     * @dataProvider invalidParametersDataProvider
     */
    public function testApiIsNotCalledIfParametersAreNotValid($parameters)
    {
        $this->setupPhp5();

        $this->mockApiResponseInvalid->expects($this->once())->method('setErrors');
        $this->mockStore->expects($this->never())->method('load');

        $featuresApi = $this->instantiateApiActionFeatures();
        $featuresApi->execute($parameters);
    }

    /**
     * @return array
     */
    public function invalidParametersDataProvider()
    {
        return [
            [''],
            [0],
            [null],
            [true],
            [false],
            ['restApiKey'],
            [[]],
            [['restApiKey' => null]]
        ];
    }

    public function testApiIsCalledUsingDefaultStoreIfNoneProvided()
    {
        $this->markTestIncomplete(
            'Can not be mocked correctly due to incorrect usage of object manager in Klevu\Search\Model\Api\Actionall'
        );

        $this->setupPhp5();

        $parameters = [
            'restApiKey' => 'klevu-rest-api-key'
        ];
        $this->mockApiResponseInvalid->expects($this->never())->method('setErrors');

        $this->mockConfigHelper->method('getHostname')->willReturn('klevu.com');
        $this->mockConfigHelper->method('getTiresUrl')->willReturn('tires.com');

        $this->mockStore->expects($this->once())->method('load');

        $this->mockResponse->expects($this->once())->method('setRawResponse')->willReturn($this->mockResponse);

        $this->mockRequest->expects($this->once())->method('send')->willReturn($this->mockResponse);

        $this->mockObjectManager->method('get')->willReturnCallback(function($class) {
            switch ($class) {
                case Features::DEFAULT_REQUEST_MODEL:
                    return $this->mockRequest;
                case Features::DEFAULT_RESPONSE_MODEL:
                    return $this->mockResponse;
                default:
                    return null;
            }
        });

        $featuresApi = $this->instantiateApiActionFeatures();
        $featuresApi->execute($parameters);
    }

    /**
     * @return void
     * @TODO when support for PHP5.6 is dropped switch to protected function setup() and remove calls from each test
     */
    private function setupPhp5()
    {
        $mockApiResponseInvalidBuilder = $this->getMockBuilder(InvalidApiResponse::class);
        if (method_exists($mockApiResponseInvalidBuilder, 'addMethods')) {
            $mockApiResponseInvalidBuilder->addMethods(['setErrors']);
        } else {
            $mockApiResponseInvalidBuilder->setMethods(['setErrors']);
        }
        $this->mockApiResponseInvalid = $mockApiResponseInvalidBuilder->disableOriginalConstructor()->getMock();

        $this->mockApiHelper = $this->getMockBuilder(ApiHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockConfigHelper = $this->getMockBuilder(ConfigHelepr::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockSearchHelper = $this->getMockBuilder(SearchHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockStoreManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockStore = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockRequest = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockResponse = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return Features
     */
    private function instantiateApiActionFeatures()
    {
        return new Features(
            $this->mockApiResponseInvalid,
            $this->mockApiHelper,
            $this->mockConfigHelper,
            $this->mockStoreManager,
            $this->mockSearchHelper,
            $this->mockStore
        );
    }
}