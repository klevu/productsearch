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
     * @var \Magento\Store\Model\ResourceModel\Store|MockObject
     */
    private $mockStoreResource;

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
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidParametersDataProvider')]
    public function testApiIsNotCalledIfParametersAreNotValid($parameters)
    {
        $this->setupPhp5();

        $this->mockApiResponseInvalid->expects($this->once())->method('setErrors');
        $this->mockStoreResource->expects($this->never())->method('load');

        $featuresApi = $this->instantiateApiActionFeatures();
        $featuresApi->execute($parameters);
    }

    /**
     * @return array
     */
    public static function invalidParametersDataProvider()
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
        $this->setupPhp5();

        $parameters = [
            'restApiKey' => 'klevu-rest-api-key'
        ];
        $this->mockApiResponseInvalid->expects($this->never())->method('setErrors');

        $this->mockConfigHelper->method('getHostname')->willReturn('klevu.com');
        $this->mockConfigHelper->method('getTiresUrl')->willReturn('tires.com');

        $this->mockStoreResource->expects($this->once())->method('load')->with($this->mockStore, null);

        $this->mockRequest->expects($this->once())->method('send')->willReturn($this->mockResponse);

        $featuresApi = $this->instantiateApiActionFeatures();
        $featuresApi->execute($parameters);
    }

    /**
     * @return void
     * @TODO when support for PHP5.6 is dropped switch to protected function setup() and remove calls from each test
     */
    private function setupPhp5()
    {
        $this->mockApiResponseInvalid = $this->getMockBuilder(InvalidResponseStub::class)
            ->onlyMethods(['setErrors'])
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->mockStoreResource = $this->createMock(\Magento\Store\Model\ResourceModel\Store::class);

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
        $features = new Features(
            $this->mockApiResponseInvalid,
            $this->mockApiHelper,
            $this->mockConfigHelper,
            $this->mockStoreManager,
            $this->mockSearchHelper,
            $this->mockStore,
            null,
            null,
            $this->mockStoreResource
        );
        $features->setRequest($this->mockRequest);
        $features->setResponse($this->mockResponse);

        return $features;
    }
}