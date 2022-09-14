<?php

namespace Klevu\Search\Test\Unit\Serevice\Admin\System\Config\Form\Field\RenderAction;

use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;
use Klevu\Search\Service\Admin\System\Config\Form\Field\RenderAction\RemovePreserveLayoutFromComment;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\Text as TextElement;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RemovePreserveLayoutFromCommentTest extends TestCase
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

    public function testApplies_FieldNotMapped()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['preserves_layout', true, false]
        ]);

        /** @var RemovePreserveLayoutFromComment $removePreserveLayoutFromComment */
        $removePreserveLayoutFromComment = $this->objectManager->getObject(
            RemovePreserveLayoutFromComment::class,
            [
                'logger' => $loggerMock,
                'getFeatures' => $getFeaturesMock,
                'fieldIds' => [
                    'klevu_search_test_field',
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_unmapped_field');

        $this->assertFalse(
            $removePreserveLayoutFromComment->applies($field,$element)
        );
    }

    public function testApplies_FeatureAvailable()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['preserves_layout', true, true]
        ]);

        /** @var RemovePreserveLayoutFromComment $removePreserveLayoutFromComment */
        $removePreserveLayoutFromComment = $this->objectManager->getObject(
            RemovePreserveLayoutFromComment::class,
            [
                'logger' => $loggerMock,
                'getFeatures' => $getFeaturesMock,
                'fieldIds' => [
                    'klevu_search_test_field',
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');

        $this->assertFalse(
            $removePreserveLayoutFromComment->applies($field,$element)
        );
    }

    public function testApplies_FeatureUnavailable()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['preserves_layout', true, false]
        ]);

        /** @var RemovePreserveLayoutFromComment $removePreserveLayoutFromComment */
        $removePreserveLayoutFromComment = $this->objectManager->getObject(
            RemovePreserveLayoutFromComment::class,
            [
                'logger' => $loggerMock,
                'getFeatures' => $getFeaturesMock,
                'fieldIds' => [
                    'klevu_search_test_field',
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');

        $this->assertTrue(
            $removePreserveLayoutFromComment->applies($field, $element)
        );
    }

    public function testBeforeRender()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['preserves_layout', true, false]
        ]);

        /** @var RemovePreserveLayoutFromComment $removePreserveLayoutFromComment */
        $removePreserveLayoutFromComment = $this->objectManager->getObject(
            RemovePreserveLayoutFromComment::class,
            [
                'logger' => $loggerMock,
                'getFeatures' => $getFeaturesMock,
                'fieldIds' => [
                    'klevu_search_test_field',
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $element = $this->getElementMock('klevu_search_test_field');
        $element->setData('comment', '
This is a test comment.
<span class="preserve-layout-comment">With some Preserve Layout specific content
which spans multiple lines
</span>
And other content <span>within a span</span>.<br />
In fact <span class="preserve-layout-comment">DOUBLE PRESERVE LAYOUT COMMENTS</span>!');

        $result = $removePreserveLayoutFromComment->beforeRender($field, $element);

        $this->assertSame([$element], $result);

        $this->assertSame('
This is a test comment.

And other content <span>within a span</span>.<br />
In fact !', $element->getData('comment'));
    }

    public function testAfterRender()
    {
        $this->setupPhp5();

        $loggerMock = $this->getLoggerMock();
        $getFeaturesMock = $this->getGetFeaturesMock([
            ['preserves_layout', true, false]
        ]);

        /** @var RemovePreserveLayoutFromComment $removePreserveLayoutFromComment */
        $removePreserveLayoutFromComment = $this->objectManager->getObject(
            RemovePreserveLayoutFromComment::class,
            [
                'logger' => $loggerMock,
                'getFeatures' => $getFeaturesMock,
                'fieldIds' => [
                    'klevu_search_test_field',
                ],
            ]
        );

        /** @var Field $field */
        $field = $this->objectManager->getObject(Field::class);
        $resultFixture = ' Foo;&Bar ' . PHP_EOL;
        $element = $this->getElementMock('klevu_search_test_field');

        $result = $removePreserveLayoutFromComment->afterRender($field, $resultFixture, $element);

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
            ->setMethods(['getHtmlId'])
            ->getMock();
        $elementMock->method('getHtmlId')
            ->willReturn($htmlId);

        return $elementMock;
    }
}
