<?php

namespace Klevu\Search\Test\Unit\Service\Admin\System\Config\Form\Field\RenderAction;

use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;
use Klevu\Search\Service\Admin\System\Config\Form\Field\RenderAction\DisableFieldIfFeatureUnavailable;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\Text as TextElement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DisableFieldIfFeatureUnavailableTest extends TestCase
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

        /** @var DisableFieldIfFeatureUnavailable $disableFieldIfFeatureUnavailable */
        $disableFieldIfFeatureUnavailable = $this->objectManager->getObject(
            DisableFieldIfFeatureUnavailable::class,
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
            $disableFieldIfFeatureUnavailable->applies($field, $element)
        );
    }

    public function testApplies_FeatureAvailable()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, true]
        ]);

        /** @var DisableFieldIfFeatureUnavailable $disableFieldIfFeatureUnavailable */
        $disableFieldIfFeatureUnavailable = $this->objectManager->getObject(
            DisableFieldIfFeatureUnavailable::class,
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
            $disableFieldIfFeatureUnavailable->applies($field, $element)
        );
    }

    public function testApplies_FeatureNotAvailable()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var DisableFieldIfFeatureUnavailable $disableFieldIfFeatureUnavailable */
        $disableFieldIfFeatureUnavailable = $this->objectManager->getObject(
            DisableFieldIfFeatureUnavailable::class,
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
            $disableFieldIfFeatureUnavailable->applies($field, $element)
        );
    }

    public function testBeforeRender()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock(
            [
                ['test_feature', true, false],
            ],
            'Test Upgrade Message'
        );

        /** @var DisableFieldIfFeatureUnavailable $disableFieldIfFeatureUnavailable */
        $disableFieldIfFeatureUnavailable = $this->objectManager->getObject(
            DisableFieldIfFeatureUnavailable::class,
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
        $element->addData([
            'label' => 'Field Label',
            'disabled' => false,
            'can_use_default_value' => true,
            'can_use_website_value' => true,
        ]);

        $this->assertFalse($element->getData('disabled'));
        $this->assertTrue($element->getData('can_use_default_value'));
        $this->assertTrue($element->getData('can_use_website_value'));
        $this->assertSame('Field Label',$element->getData('label'));

        $result = $disableFieldIfFeatureUnavailable->beforeRender($field, $element);

        $this->assertSame([$element], $result);

        $this->assertTrue($element->getData('disabled'));
        $this->assertFalse($element->getData('can_use_default_value'));
        $this->assertFalse($element->getData('can_use_website_value'));
        $this->assertSame(
            'Field Label <div class="klevu-upgrade-block">Test Upgrade Message</div>',
            $element->getData('label')
        );
    }

    public function testAfterRender()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['test_feature', true, false]
        ]);

        /** @var DisableFieldIfFeatureUnavailable $disableFieldIfFeatureUnavailable */
        $disableFieldIfFeatureUnavailable = $this->objectManager->getObject(
            DisableFieldIfFeatureUnavailable::class,
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
        $resultFixture = ' Foo;&Bar ' . PHP_EOL;
        $element = $this->getElementMock('klevu_search_unmapped_field');

        $result = $disableFieldIfFeatureUnavailable->afterRender($field, $resultFixture, $element);

        $this->assertSame($resultFixture, $result);
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
     * @param $upgradeMessage
     * @return GetFeaturesInterface
     */
    private function getGetFeaturesMock(
        array $isFeatureAvailableReturnMap,
        $upgradeMessage = null
    ) {
        /** @var AccountFeaturesInterface $accountFeaturesMock */
        $accountFeaturesMock = $this->getMockBuilder(AccountFeaturesInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $accountFeaturesMock->method('isFeatureAvailable')
            ->willReturnMap($isFeatureAvailableReturnMap);
        $accountFeaturesMock->method('getUpgradeMessage')
            ->willReturn($upgradeMessage);

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
            ->setMethods(['getHtmlId'])
            ->getMock();
        $elementMock->method('getHtmlId')
            ->willReturn($htmlId);

        return $elementMock;
    }
}
