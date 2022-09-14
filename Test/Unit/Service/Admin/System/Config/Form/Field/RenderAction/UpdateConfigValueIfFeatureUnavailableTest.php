<?php

namespace Klevu\Search\Test\Unit\Service\Admin\System\Config\Form\Field\RenderAction;

use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;
use Klevu\Search\Service\Admin\System\Config\Form\Field\RenderAction\UpdateConfigValueIfFeatureUnavailable;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\Form\Element\Text as TextElement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdateConfigValueIfFeatureUnavailableTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = new ObjectManager($this);
    }

    public function testApplies_InvalidFieldUpdateConfig()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->exactly(4))
            ->method('warning')
            ->withConsecutive(
                ['Invalid field update config "missing_element_id", element_id key must be a non-empty string'],
                ['Invalid field update config "missing_feature", feature key must be a non-empty string'],
                ['Invalid field update config "missing_value", value key must be present'],
                ['Invalid field update config "invalid_allowed_values", allowed_values must be array']
            );
        $requestMock = $this->getRequestMock();
        $requestMock->method('getParam')
            ->with('store')
            ->willReturn('1');
        $storeManagerMock = $this->getStoreManagerMock();
        $scopeConfigMock = $this->getScopeConfigMock();
        $scopeConfigWriterMock = $this->getScopeConfigWriterMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var UpdateConfigValueIfFeatureUnavailable $updateConfigValueIfFeatureUnavailable */
        $updateConfigValueIfFeatureUnavailable = $this->objectManager->getObject(
            UpdateConfigValueIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'request' => $requestMock,
                'storeManager' => $storeManagerMock,
                'scopeConfig' => $scopeConfigMock,
                'scopeConfigWriter' => $scopeConfigWriterMock,
                'getFeatures' => $getFeaturesMock,
                'fieldUpdateConfig' => [
                    'missing_element_id' => [
                        'feature' => 'test_feature',
                        'value' => 0,
                        'allowed_values' => [1],
                    ],
                    'missing_feature' => [
                        'element_id' => 'test_element',
                        'value' => 0,
                        'allowed_values' => [1],
                    ],
                    'missing_value' => [
                        'element_id' => 'test_element',
                        'feature' => 'test_feature',
                        'allowed_values' => [1],
                    ],
                    'invalid_allowed_values' => [
                        'element_id' => 'test_element',
                        'feature' => 'test_feature',
                        'value' => 0,
                        'allowed_values' => 1,
                    ],
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_unmapped_field');

        $this->assertFalse(
            $updateConfigValueIfFeatureUnavailable->applies($field, $element)
        );
    }

    public function testApplies_FieldNotMapped()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->never())->method('warning');
        $requestMock = $this->getRequestMock();
        $requestMock->method('getParam')
            ->with('store')
            ->willReturn('1');
        $storeManagerMock = $this->getStoreManagerMock();
        $scopeConfigMock = $this->getScopeConfigMock();
        $scopeConfigWriterMock = $this->getScopeConfigWriterMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var UpdateConfigValueIfFeatureUnavailable $updateConfigValueIfFeatureUnavailable */
        $updateConfigValueIfFeatureUnavailable = $this->objectManager->getObject(
            UpdateConfigValueIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'request' => $requestMock,
                'storeManager' => $storeManagerMock,
                'scopeConfig' => $scopeConfigMock,
                'scopeConfigWriter' => $scopeConfigWriterMock,
                'getFeatures' => $getFeaturesMock,
                'fieldUpdateConfig' => [
                    'klevu_search_test_field' => [
                        'element_id' => 'klevu_search_test_field',
                        'feature' => 'test_feature',
                        'value' => 0,
                        'allowed_values' => [1],
                    ],
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_unmapped_field');

        $this->assertFalse(
            $updateConfigValueIfFeatureUnavailable->applies($field, $element)
        );
    }

    public function testApplies_FeatureAvailable()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->never())->method('warning');
        $requestMock = $this->getRequestMock();
        $requestMock->method('getParam')
            ->with('store')
            ->willReturn('1');
        $storeManagerMock = $this->getStoreManagerMock();
        $scopeConfigMock = $this->getScopeConfigMock();
        $scopeConfigWriterMock = $this->getScopeConfigWriterMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, true]
        ]);

        /** @var UpdateConfigValueIfFeatureUnavailable $updateConfigValueIfFeatureUnavailable */
        $updateConfigValueIfFeatureUnavailable = $this->objectManager->getObject(
            UpdateConfigValueIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'request' => $requestMock,
                'storeManager' => $storeManagerMock,
                'scopeConfig' => $scopeConfigMock,
                'scopeConfigWriter' => $scopeConfigWriterMock,
                'getFeatures' => $getFeaturesMock,
                'fieldUpdateConfig' => [
                    'klevu_search_test_field' => [
                        'element_id' => 'klevu_search_test_field',
                        'feature' => 'test_feature',
                        'value' => 0,
                        'allowed_values' => [1],
                    ],
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');

        $this->assertFalse(
            $updateConfigValueIfFeatureUnavailable->applies($field, $element)
        );
    }

    public function testApplies_FeatureUnavailable()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->never())->method('warning');
        $requestMock = $this->getRequestMock();
        $requestMock->method('getParam')
            ->with('store')
            ->willReturn('1');
        $storeManagerMock = $this->getStoreManagerMock();
        $scopeConfigMock = $this->getScopeConfigMock();
        $scopeConfigWriterMock = $this->getScopeConfigWriterMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var UpdateConfigValueIfFeatureUnavailable $updateConfigValueIfFeatureUnavailable */
        $updateConfigValueIfFeatureUnavailable = $this->objectManager->getObject(
            UpdateConfigValueIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'request' => $requestMock,
                'storeManager' => $storeManagerMock,
                'scopeConfig' => $scopeConfigMock,
                'scopeConfigWriter' => $scopeConfigWriterMock,
                'getFeatures' => $getFeaturesMock,
                'fieldUpdateConfig' => [
                    'klevu_search_test_field' => [
                        'element_id' => 'klevu_search_test_field',
                        'feature' => 'test_feature',
                        'value' => 0,
                        'allowed_values' => [1],
                    ],
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');

        $this->assertTrue(
            $updateConfigValueIfFeatureUnavailable->applies($field, $element)
        );
    }

    public function testBeforeRender_WithoutStoreParam()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->never())->method('warning');
        $requestMock = $this->getRequestMock();
        $requestMock->method('getParam')
            ->with('store')
            ->willReturn(null);
        $storeManagerMock = $this->getStoreManagerMock();
        $storeManagerMock->expects($this->never())
            ->method('getStore');
        $scopeConfigMock = $this->getScopeConfigMock();
        $scopeConfigMock->expects($this->never())
            ->method('getValue');
        $scopeConfigWriterMock = $this->getScopeConfigWriterMock();
        $scopeConfigWriterMock->expects($this->never())
            ->method('save');
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var UpdateConfigValueIfFeatureUnavailable $updateConfigValueIfFeatureUnavailable */
        $updateConfigValueIfFeatureUnavailable = $this->objectManager->getObject(
            UpdateConfigValueIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'request' => $requestMock,
                'storeManager' => $storeManagerMock,
                'scopeConfig' => $scopeConfigMock,
                'scopeConfigWriter' => $scopeConfigWriterMock,
                'getFeatures' => $getFeaturesMock,
                'fieldUpdateConfig' => [
                    'klevu_search_test_field' => [
                        'element_id' => 'klevu_search_test_field',
                        'feature' => 'test_feature',
                        'value' => 0,
                        'allowed_values' => [1],
                    ],
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');
        $element->setData('value', '2');

        $result = $updateConfigValueIfFeatureUnavailable->beforeRender($field, $element);

        $this->assertSame([$element], $result);
        $this->assertSame('2', $element->getData('value'));
    }

    public function testBeforeRender_WithInvalidStoreParam()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->never())->method('warning');
        $loggerMock->expects($this->once())
            ->method('error');
        $requestMock = $this->getRequestMock();
        $requestMock->method('getParam')
            ->with('store')
            ->willReturn('987');
        $storeManagerMock = $this->getStoreManagerMock();
        $storeManagerMock->method('getStore')
            ->with('987')
            ->willThrowException(NoSuchEntityException::singleField('entity_id', '987'));
        $scopeConfigMock = $this->getScopeConfigMock();
        $scopeConfigMock->expects($this->never())
            ->method('getValue');
        $scopeConfigWriterMock = $this->getScopeConfigWriterMock();
        $scopeConfigWriterMock->expects($this->never())
            ->method('save');
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var UpdateConfigValueIfFeatureUnavailable $updateConfigValueIfFeatureUnavailable */
        $updateConfigValueIfFeatureUnavailable = $this->objectManager->getObject(
            UpdateConfigValueIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'request' => $requestMock,
                'storeManager' => $storeManagerMock,
                'scopeConfig' => $scopeConfigMock,
                'scopeConfigWriter' => $scopeConfigWriterMock,
                'getFeatures' => $getFeaturesMock,
                'fieldUpdateConfig' => [
                    'klevu_search_test_field' => [
                        'element_id' => 'klevu_search_test_field',
                        'feature' => 'test_feature',
                        'value' => 0,
                        'allowed_values' => [1],
                    ],
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');
        $element->setData('value', '2');

        $result = $updateConfigValueIfFeatureUnavailable->beforeRender($field, $element);

        $this->assertSame([$element], $result);
        $this->assertSame('2', $element->getData('value'));
    }

    public function testBeforeRender_WithDifferentValue()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->never())->method('warning');
        $loggerMock->expects($this->once())
            ->method('debug')
            ->with('Automatically updated config value for "klevu_search/test/field" following feature check');
        $requestMock = $this->getRequestMock();
        $requestMock->method('getParam')
            ->with('store')
            ->willReturn('1');
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->method('getId')
            ->willReturn('1');
        $storeManagerMock = $this->getStoreManagerMock();
        $storeManagerMock->method('getStore')
            ->with('1')
            ->willReturn($storeMock);
        $scopeConfigMock = $this->getScopeConfigMock();
        $scopeConfigMock->method('getValue')
            ->with('klevu_search/test/field', 'stores', 1)
            ->willReturn('2');
        $scopeConfigWriterMock = $this->getScopeConfigWriterMock();
        $scopeConfigWriterMock->expects($this->once())
            ->method('save')
            ->with('klevu_search/test/field', 0, 'stores', 1);
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var UpdateConfigValueIfFeatureUnavailable $updateConfigValueIfFeatureUnavailable */
        $updateConfigValueIfFeatureUnavailable = $this->objectManager->getObject(
            UpdateConfigValueIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'request' => $requestMock,
                'storeManager' => $storeManagerMock,
                'scopeConfig' => $scopeConfigMock,
                'scopeConfigWriter' => $scopeConfigWriterMock,
                'getFeatures' => $getFeaturesMock,
                'fieldUpdateConfig' => [
                    'klevu_search_test_field' => [
                        'element_id' => 'klevu_search_test_field',
                        'feature' => 'test_feature',
                        'value' => 0,
                        'allowed_values' => [1],
                    ],
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');
        $element->setData('value', '2');

        $result = $updateConfigValueIfFeatureUnavailable->beforeRender($field, $element);

        $this->assertSame([$element], $result);
        $this->assertEquals(0, $element->getData('value'));
    }

    public function testBeforeRender_WithValue()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->never())->method('warning');
        $loggerMock->expects($this->never())
            ->method('debug');
        $requestMock = $this->getRequestMock();
        $requestMock->method('getParam')
            ->with('store')
            ->willReturn('1');
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->method('getId')
            ->willReturn('1');
        $storeManagerMock = $this->getStoreManagerMock();
        $storeManagerMock->method('getStore')
            ->with('1')
            ->willReturn($storeMock);
        $scopeConfigMock = $this->getScopeConfigMock();
        $scopeConfigMock->method('getValue')
            ->with('klevu_search/test/field', 'stores', 1)
            ->willReturn('0');
        $scopeConfigWriterMock = $this->getScopeConfigWriterMock();
        $scopeConfigWriterMock->expects($this->never())
            ->method('save');
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var UpdateConfigValueIfFeatureUnavailable $updateConfigValueIfFeatureUnavailable */
        $updateConfigValueIfFeatureUnavailable = $this->objectManager->getObject(
            UpdateConfigValueIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'request' => $requestMock,
                'storeManager' => $storeManagerMock,
                'scopeConfig' => $scopeConfigMock,
                'scopeConfigWriter' => $scopeConfigWriterMock,
                'getFeatures' => $getFeaturesMock,
                'fieldUpdateConfig' => [
                    'klevu_search_test_field' => [
                        'element_id' => 'klevu_search_test_field',
                        'feature' => 'test_feature',
                        'value' => 0,
                        'allowed_values' => [1],
                    ],
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');
        $element->setData('value', '0');

        $result = $updateConfigValueIfFeatureUnavailable->beforeRender($field, $element);

        $this->assertSame([$element], $result);
        $this->assertSame('0', $element->getData('value'));
    }

    public function testBeforeRender_WithAllowedValue()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->never())->method('warning');
        $loggerMock->expects($this->never())
            ->method('debug');
        $requestMock = $this->getRequestMock();
        $requestMock->method('getParam')
            ->with('store')
            ->willReturn('1');
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->method('getId')
            ->willReturn('1');
        $storeManagerMock = $this->getStoreManagerMock();
        $storeManagerMock->method('getStore')
            ->with('1')
            ->willReturn($storeMock);
        $scopeConfigMock = $this->getScopeConfigMock();
        $scopeConfigMock->method('getValue')
            ->with('klevu_search/test/field', 'stores', 1)
            ->willReturn('0');
        $scopeConfigWriterMock = $this->getScopeConfigWriterMock();
        $scopeConfigWriterMock->expects($this->never())
            ->method('save');
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var UpdateConfigValueIfFeatureUnavailable $updateConfigValueIfFeatureUnavailable */
        $updateConfigValueIfFeatureUnavailable = $this->objectManager->getObject(
            UpdateConfigValueIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'request' => $requestMock,
                'storeManager' => $storeManagerMock,
                'scopeConfig' => $scopeConfigMock,
                'scopeConfigWriter' => $scopeConfigWriterMock,
                'getFeatures' => $getFeaturesMock,
                'fieldUpdateConfig' => [
                    'klevu_search_test_field' => [
                        'element_id' => 'klevu_search_test_field',
                        'feature' => 'test_feature',
                        'value' => 0,
                        'allowed_values' => [1],
                    ],
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');
        $element->setData('value', '1');

        $result = $updateConfigValueIfFeatureUnavailable->beforeRender($field, $element);

        $this->assertSame([$element], $result);
        $this->assertSame('1', $element->getData('value'));
    }

    public function testBeforeRender_WithConfigPath()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->never())->method('warning');
        $loggerMock->expects($this->once())
            ->method('debug')
            ->with('Automatically updated config value for "klevu/some_other/config_path" following feature check');
        $requestMock = $this->getRequestMock();
        $requestMock->method('getParam')
            ->with('store')
            ->willReturn('1');
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->method('getId')
            ->willReturn('1');
        $storeManagerMock = $this->getStoreManagerMock();
        $storeManagerMock->method('getStore')
            ->with('1')
            ->willReturn($storeMock);
        $scopeConfigMock = $this->getScopeConfigMock();
        $scopeConfigMock->method('getValue')
            ->with('klevu/some_other/config_path', 'stores', 1)
            ->willReturn('2');
        $scopeConfigWriterMock = $this->getScopeConfigWriterMock();
        $scopeConfigWriterMock->expects($this->once())
            ->method('save')
            ->with('klevu/some_other/config_path', 0, 'stores', 1);
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var UpdateConfigValueIfFeatureUnavailable $updateConfigValueIfFeatureUnavailable */
        $updateConfigValueIfFeatureUnavailable = $this->objectManager->getObject(
            UpdateConfigValueIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'request' => $requestMock,
                'storeManager' => $storeManagerMock,
                'scopeConfig' => $scopeConfigMock,
                'scopeConfigWriter' => $scopeConfigWriterMock,
                'getFeatures' => $getFeaturesMock,
                'fieldUpdateConfig' => [
                    'klevu_search_test_field' => [
                        'element_id' => 'klevu_search_test_field',
                        'feature' => 'test_feature',
                        'value' => 0,
                        'allowed_values' => [1],
                    ],
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');
        $element->setData('value', '2');
        $element->setData('field_config', ['config_path' => 'klevu/some_other/config_path']);

        $result = $updateConfigValueIfFeatureUnavailable->beforeRender($field, $element);

        $this->assertSame([$element], $result);
        $this->assertEquals(0, $element->getData('value'));
    }

    public function testAfterRender()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $loggerMock->expects($this->never())->method('warning');
        $requestMock = $this->getRequestMock();
        $requestMock->method('getParam')
            ->with('store')
            ->willReturn('1');
        $storeManagerMock = $this->getStoreManagerMock();
        $scopeConfigMock = $this->getScopeConfigMock();
        $scopeConfigWriterMock = $this->getScopeConfigWriterMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var UpdateConfigValueIfFeatureUnavailable $updateConfigValueIfFeatureUnavailable */
        $updateConfigValueIfFeatureUnavailable = $this->objectManager->getObject(
            UpdateConfigValueIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'request' => $requestMock,
                'storeManager' => $storeManagerMock,
                'scopeConfig' => $scopeConfigMock,
                'scopeConfigWriter' => $scopeConfigWriterMock,
                'getFeatures' => $getFeaturesMock,
                'fieldUpdateConfig' => [
                    'klevu_search_test_field' => [
                        'element_id' => 'klevu_search_test_field',
                        'feature' => 'test_feature',
                        'value' => 0,
                        'allowed_values' => [1],
                    ],
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $resultFixture = ' Foo;&Bar ' . PHP_EOL;
        $element = $this->getElementMock('klevu_search_test_field');

        $result = $updateConfigValueIfFeatureUnavailable->afterRender($field, $resultFixture, $element);

        $this->assertSame($resultFixture, $result);
    }

    /**
     * @return MockObject|LoggerInterface&MockObject
     */
    private function getLoggerMock()
    {
        return $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return RequestInterface&MockObject|MockObject
     */
    private function getRequestMock()
    {
        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $requestMock;
    }

    /**
     * @return StoreManagerInterface&MockObject|MockObject
     */
    private function getStoreManagerMock()
    {
        $storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $storeManagerMock;
    }

    /**
     * @return ScopeConfigInterface&MockObject|MockObject
     */
    private function getScopeConfigMock()
    {
        $scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $scopeConfigMock;
    }

    /**
     * @return ScopeConfigWriterInterface&MockObject|MockObject
     */
    private function getScopeConfigWriterMock()
    {
        $scopeConfigWriterMock = $this->getMockBuilder(ScopeConfigWriterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $scopeConfigWriterMock;
    }

    /**
     * @param array $isFeatureAvailableReturnMap
     * @return GetFeaturesInterface&MockObject|MockObject
     */
    private function getGetFeaturesMock(array $isFeatureAvailableReturnMap)
    {
        /** @var AccountFeaturesInterface $accountFeaturesMock */
        $accountFeaturesMock = $this->getMockBuilder(AccountFeaturesInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $accountFeaturesMock->method('isFeatureAvailable')
            ->willReturnMap($isFeatureAvailableReturnMap);

        /** @var MockObject&GetFeaturesInterface $getFeaturesMock */
        $getFeaturesMock = $this->getMockBuilder(GetFeaturesInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $getFeaturesMock->method('execute')
            ->willReturnCallback(static function ($storeParam) use ($accountFeaturesMock) {
                switch ($storeParam) {
                    case '987':
                        $return = null;
                        break;

                    default:
                        $return = $accountFeaturesMock;
                        break;
                }

                return $return;
            });

        return $getFeaturesMock;
    }

    /**
     * @param $htmlId
     * @return TextElement&MockObject|MockObject
     */
    private function getElementMock($htmlId)
    {
        $htmlIdParts = explode('_', $htmlId);
        $fieldId = array_pop($htmlIdParts);
        $groupId = array_pop($htmlIdParts);
        $path = implode('_', $htmlIdParts);

        $containerMock = $this->getMockBuilder(Fieldset::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHtmlId'])
            ->getMock();
        $containerMock->method('getHtmlId')
            ->willReturn($path . '_' . $groupId);
        $containerMock->setData('group', [
            'id' => $groupId,
            'path' => $path,
        ]);

        /** @var TextElement $element */
        $elementMock = $this->getMockBuilder(TextElement::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHtmlId'])
            ->getMock();
        $elementMock->method('getHtmlId')
            ->willReturn($htmlId);
        $elementMock->setData('container', $containerMock);

        return $elementMock;
    }
}
