<?php

namespace Klevu\Search\Test\Unit\Service\Admin\System\Config\Form\Field\RenderAction;

use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;
use Klevu\Search\Service\Admin\System\Config\Form\Field\RenderAction\RemoveFieldIfFeatureUnavailable;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\Text as TextElement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RemoveFieldIfFeatureUnavailableTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testApplies_FieldNotMapped()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var RemoveFieldIfFeatureUnavailable $removeFieldIfFeatureUnavailable */
        $removeFieldIfFeatureUnavailable = $this->objectManager->getObject(
            RemoveFieldIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'getFeatures' => $getFeaturesMock,
                'fieldToFeatureMap' => [
                    'klevu_search_test_field' => 'test_feature',
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_unmapped_field');

        $this->assertFalse(
            $removeFieldIfFeatureUnavailable->applies($field, $element)
        );
    }

    public function testApplies_FeatureAvailable()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, true]
        ]);

        /** @var RemoveFieldIfFeatureUnavailable $removeFieldIfFeatureUnavailable */
        $removeFieldIfFeatureUnavailable = $this->objectManager->getObject(
            RemoveFieldIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'getFeatures' => $getFeaturesMock,
                'fieldToFeatureMap' => [
                    'klevu_search_test_field' => 'test_feature',
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');

        $this->assertFalse(
            $removeFieldIfFeatureUnavailable->applies($field, $element)
        );
    }

    public function testApplies_FeatureNotAvailable()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var RemoveFieldIfFeatureUnavailable $removeFieldIfFeatureUnavailable */
        $removeFieldIfFeatureUnavailable = $this->objectManager->getObject(
            RemoveFieldIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'getFeatures' => $getFeaturesMock,
                'fieldToFeatureMap' => [
                    'klevu_search_test_field' => 'test_feature',
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');

        $this->assertTrue(
            $removeFieldIfFeatureUnavailable->applies($field, $element)
        );
    }

    public function testBeforeRender()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var RemoveFieldIfFeatureUnavailable $removeFieldIfFeatureUnavailable */
        $removeFieldIfFeatureUnavailable = $this->objectManager->getObject(
            RemoveFieldIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'getFeatures' => $getFeaturesMock,
                'fieldToFeatureMap' => [
                    'klevu_search_test_field' => 'test_feature',
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');

        $result = $removeFieldIfFeatureUnavailable->beforeRender($field, $element);

        $this->assertSame([$element], $result);
    }

    public function testAfterRender()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var RemoveFieldIfFeatureUnavailable $removeFieldIfFeatureUnavailable */
        $removeFieldIfFeatureUnavailable = $this->objectManager->getObject(
            RemoveFieldIfFeatureUnavailable::class,
            [
                'logger' => $loggerMock,
                'getFeatures' => $getFeaturesMock,
                'fieldToFeatureMap' => [
                    'klevu_search_test_field' => 'test_feature',
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $resultFixture = '<td>
<input type="text" name="test_field" value="foo" />
</td>';
        $element = $this->getElementMock('klevu_search_test_field');

        $result = $removeFieldIfFeatureUnavailable->afterRender($field, $resultFixture, $element);

        $this->assertSame('', $result);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = new ObjectManager($this);
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

        /** @var GetFeaturesInterface $getFeaturesMock */
        $getFeaturesMock = $this->getMockBuilder(GetFeaturesInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $getFeaturesMock->method('execute')
            ->willReturn($accountFeaturesMock);

        return $getFeaturesMock;
    }

    /**
     * @param $htmlId
     * @return TextElement&MockObject|MockObject
     */
    private function getElementMock($htmlId)
    {
        /** @var TextElement $element */
        $elementMock = $this->getMockBuilder(TextElement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $elementMock->method('getHtmlId')
            ->willReturn($htmlId);

        return $elementMock;
    }
}
