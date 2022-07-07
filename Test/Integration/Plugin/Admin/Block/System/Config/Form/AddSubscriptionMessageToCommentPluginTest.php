<?php

namespace Klevu\Search\Test\Integration\Plugin\Admin\Block\System\Config\Form;

use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;
use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Plugin\Admin\System\Config\Form\Field\AddSubscriptionMessagePlugin;
use Klevu\Search\Service\Account\Model\AccountFeatures;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\App\State as AppAreaState;
use Magento\TestFramework\Interception\PluginList;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class AddSubscriptionMessageToCommentPluginTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    private $pluginName = 'Klevu_Search::AdminFormFieldAddSubscriptionMessageToComment';

    public function testTheModuleDoesNotInterceptsCallsToTheFieldInGlobalScope()
    {
        $this->setupPhp5();

        $this->setArea(Area::AREA_GLOBAL);
        $pluginInfo = $this->getSystemConfigFormFieldPluginInfo();
        $this->assertArrayNotHasKey($this->pluginName, $pluginInfo);

        $this->tearDownPhp5();
    }

    public function testTheModuleInterceptsCallsToTheFieldInAdminScope()
    {
        $this->setupPhp5();

        $this->setArea(Area::AREA_ADMINHTML);
        $pluginInfo = $this->getSystemConfigFormFieldPluginInfo();
        $this->assertSame(AddSubscriptionMessagePlugin::class, $pluginInfo[$this->pluginName]['instance']);

        $this->tearDownPhp5();
    }

    public function testBeforeRenderCanBeCalled()
    {
        $this->setupPhp5();

        $testFieldId = 'some_test_field';
        $subject = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $element = $this->objectManager->create(Text::class);
        $element->setId($testFieldId);

        $plugin = $this->objectManager->create(AddSubscriptionMessagePlugin::class, [
            'fieldsToShowMessageFor' => [
                'klevu_search_searchlanding_landenabled' => AccountFeatures::PM_FEATUREFLAG_PRESERVES_LAYOUT
            ]
        ]);

        $newElement = $plugin->beforeRender($subject, $element)[0];
        $data = $newElement->getData();

        if (method_exists($this, 'assertIsArray')) {
            $this->assertisArray($data);
        } else {
            $this->assertTrue(is_array($data), 'Is Array');
        }
        $this->assertSame($testFieldId, $newElement->getId());
    }

    public function testMessageIsNotShownForOtherFields()
    {
        $this->setupPhp5();
        $fieldId = 'some_other_field';
        $originalComment = 'some comment sting';

        $mockSubject = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $origField = $this->objectManager->create(Text::class);
        $origField->setId($fieldId);
        $origField->setComment($originalComment);

        $mockAccountFeatures = $this->getMockBuilder(AccountFeaturesInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountFeatures->method('isFeatureAvailable')->willReturn(true);
        $mockAccountFeatures->expects($this->never())->method('getPreserveLayoutMessage');

        $mockGetFeatures = $this->getMockBuilder(GetFeaturesInterface::class)->disableOriginalConstructor()->getMock();
        $mockGetFeatures->method('execute')->willReturn($mockAccountFeatures);

        $mockRequest = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockRequest->method('getParam')->willReturn(1);

        $plugin = $this->objectManager->create(AddSubscriptionMessagePlugin::class, [
            'getFeatures' => $mockGetFeatures,
            'request' => $mockRequest,
            'fieldsToShowMessageFor' => [
                'klevu_search_searchlanding_landenabled' => AccountFeatures::PM_FEATUREFLAG_PRESERVES_LAYOUT
            ]
        ]);
        $field = $plugin->beforeRender($mockSubject, $origField)[0];

        $this->assertSame($fieldId, $field->getId());
        $this->assertSame($originalComment, $field->getComment());
    }

    /**
     * @dataProvider fieldsToShowMessage
     */
    public function testMessageIsNotDisplayedIfSubscriptionIsActive()
    {
        $this->setupPhp5();
        $notTargetId = 'some_other_field';
        $originalComment = 'some comment sting';

        $mockSubject = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $origField = $this->objectManager->create(Text::class);
        $origField->setId($notTargetId);
        $origField->setComment($originalComment);

        $mockAccountFeatures = $this->getMockBuilder(AccountFeaturesInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountFeatures->method('isFeatureAvailable')->willReturn(true);
        $mockAccountFeatures->expects($this->never())->method('getPreserveLayoutMessage');

        $mockGetFeatures = $this->getMockBuilder(GetFeaturesInterface::class)->disableOriginalConstructor()->getMock();
        $mockGetFeatures->method('execute')->willReturn($mockAccountFeatures);

        $mockRequest = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockRequest->method('getParam')->willReturn(1);

        $plugin = $this->objectManager->create(AddSubscriptionMessagePlugin::class, [
            'getFeatures' => $mockGetFeatures,
            'request' => $mockRequest,
            'fieldsToShowMessageFor' => [
                'klevu_search_searchlanding_landenabled' => AccountFeatures::PM_FEATUREFLAG_PRESERVES_LAYOUT
            ]
        ]);
        $field = $plugin->beforeRender($mockSubject, $origField)[0];

        $this->assertSame($notTargetId, $field->getId());
        $this->assertSame($originalComment, $field->getComment());
    }

    /**
     * @dataProvider fieldsToShowMessage
     */
    public function testMessageIsDisplayedIfSubscriptionIsNotActive($fieldToShowMessage)
    {
        $this->setupPhp5();
        $originalComment = 'some comment sting';
        $preservesLayoutMessage = 'Preserves Layout Message';

        $mockSubject = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $origField = $this->objectManager->create(Text::class);
        $origField->setId($fieldToShowMessage);
        $origField->setComment($originalComment);

        $mockAccountFeatures = $this->getMockBuilder(AccountFeaturesInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountFeatures->method('isFeatureAvailable')->willReturn(false);
        $mockAccountFeatures->method('getPreserveLayoutMessage')->willReturn($preservesLayoutMessage);

        $mockGetFeatures = $this->getMockBuilder(GetFeaturesInterface::class)->disableOriginalConstructor()->getMock();
        $mockGetFeatures->method('execute')->willReturn($mockAccountFeatures);

        $mockRequest = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockRequest->method('getParam')->willReturn(1);

        $plugin = $this->objectManager->create(AddSubscriptionMessagePlugin::class, [
            'getFeatures' => $mockGetFeatures,
            'request' => $mockRequest,
            'fieldsToShowMessageFor' => [
                'klevu_search_searchlanding_landenabled' => AccountFeatures::PM_FEATUREFLAG_PRESERVES_LAYOUT
            ]
        ]);
        $field = $plugin->beforeRender($mockSubject, $origField)[0];

        $this->assertSame($fieldToShowMessage, $field->getId());
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString($preservesLayoutMessage, $field->getComment());
        } else {
            $this->assertContains($preservesLayoutMessage, $field->getComment());
        }
    }

    /**
     * @return string[][]
     */
    public function fieldsToShowMessage()
    {
        return [
            ['klevu_search_searchlanding_landenabled']
        ];
    }

    /**
     * @return array[]
     */
    private function getSystemConfigFormFieldPluginInfo()
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->get(PluginList::class);

        return $pluginList->get(Field::class, []);
    }

    /**
     * @param string $code
     *
     * @return void
     * @throws LocalizedException
     */
    private function setArea($code)
    {
        /** @var AppAreaState $appArea */
        $appArea = $this->objectManager->get(AppAreaState::class);
        $appArea->setAreaCode($code);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @return void
     * @todo Move to tearDown when PHP 5.x is no longer supported
     */
    private function tearDownPhp5()
    {
        $this->setArea(Area::AREA_GLOBAL);
    }
}
