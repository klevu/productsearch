<?php

namespace Klevu\Search\Test\Integration\Plugin\Admin\Block\System\Config\Form;

use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;
use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Plugin\Admin\System\Config\Form\Field\DisableFieldPlugin;
use Klevu\Search\Service\Account\Model\AccountFeatures;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Area;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Block\Form\Container;
use Magento\TestFramework\App\State as AppAreaState;
use Magento\TestFramework\Interception\PluginList;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class DisableKlevuFieldsIfFeatureNotEnabledTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    private $pluginName = 'Klevu_Search::AdminFormFieldDisableFieldsIfNotEnabled';

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
        $this->assertSame(DisableFieldPlugin::class, $pluginInfo[$this->pluginName]['instance']);

        $this->tearDownPhp5();
    }

    public function testFieldsInOtherSectionsAreNotDisabled()
    {
        $this->setupPhp5();
        $configPath = ['section' => 'general', 'group' => 'some', 'field' => 'field'];

        $mockGetFeatures = $this->createMockGetFeatures(false);

        $plugin = $this->objectManager->create(DisableFieldPlugin::class, [
            'getFeatures' => $mockGetFeatures,
            'sectionsToDisable' => [
                'klevu_search' => AccountFeatures::PM_FEATUREFLAG_CATEGORY_NAVIGATION
            ],
            'fieldsToDisable' => [
                'klevu_search_categorylanding_enabledcategorynavigation' => AccountFeatures::PM_FEATUREFLAG_CATEGORY_NAVIGATION
            ]
        ]);

        $mockSubject = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $origField = $this->createField($configPath);

        $element = $plugin->beforeRender($mockSubject, $origField)[0];

        $this->assertSame(
            $configPath['section'] . '_' . $configPath['group'] . '_' . $configPath['field'],
            $element->getHtmlId()
        );
        $this->assertFalse($element->getData('disabled'));
        $this->assertNotSame(0, $element->getData('value'));
    }

    public function testFieldsInThisSectionAreNotDisabledIfActive()
    {
        $this->setupPhp5();
        $configPath = ['section' => 'klevu_search', 'group' => 'categorylanding', 'field' => 'some_enabled_field'];

        $mockGetFeatures = $this->createMockGetFeatures();

        $plugin = $this->objectManager->create(DisableFieldPlugin::class, [
            'getFeatures' => $mockGetFeatures,
            'fieldsToDisable' => [
                'klevu_search_categorylanding_enabledcategorynavigation' => AccountFeatures::PM_FEATUREFLAG_CATEGORY_NAVIGATION
            ]
        ]);

        $mockSubject = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $origField = $this->createField($configPath);

        /** @var AbstractElement $element */
        $element = $plugin->beforeRender($mockSubject, $origField)[0];

        $this->assertSame(
            $configPath['section'] . '_' . $configPath['group'] . '_' . $configPath['field'],
            $element->getHtmlId()
        );
        $this->assertFalse($element->getData('disabled'));
        $this->assertTrue($element->getData('can_use_default_value'));
        $this->assertTrue($element->getData('can_use_website_value'));
        $this->assertSame('some_test_string', $element->getData('value'));
    }

    public function testFieldsInThisSectionAreDisabledIfNotActive()
    {
        $this->setupPhp5();
        $configPath = [
            'section' => 'klevu_search',
            'group' => 'categorylanding',
            'field' => 'enabledcategorynavigation'
        ];

        $mockGetFeatures = $this->createMockGetFeatures(true, false);

        $plugin = $this->objectManager->create(DisableFieldPlugin::class, [
            'getFeatures' => $mockGetFeatures,
            'fieldsToDisable' => [
                'klevu_search_categorylanding_enabledcategorynavigation' => AccountFeatures::PM_FEATUREFLAG_CATEGORY_NAVIGATION
            ]
        ]);
        $mockSubject = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $origField = $this->createField($configPath);

        /** @var AbstractElement $element */
        $element = $plugin->beforeRender($mockSubject, $origField)[0];

        $this->assertSame(
            $configPath['section'] . '_' . $configPath['group'] . '_' . $configPath['field'],
            $element->getHtmlId()
        );
        $this->assertTrue($element->getData('disabled'));
        $this->assertSame(0, $element->getData('value'));
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString("<div class='klevu-upgrade-block'>", $element->getData('label'));
        } else {
            $this->assertContains("<div class='klevu-upgrade-block'>", $element->getData('label'));
        }
        $this->assertFalse($element->getData('can_use_default_value'));
        $this->assertFalse($element->getData('can_use_website_value'));
    }

    /**
     * @param array $$configPath e.g ['section' => 'klevu_search', 'group' => 'general' , 'field' => 'enabled']
     * @param string $fieldValue
     * @param string $fieldType
     *
     * @return AbstractElement
     */
    private function createField(array $configPath, $fieldValue = 'some_test_string', $fieldType = Text::class)
    {
        $mockBuilder = $this->getMockBuilder(Container::class);
        if (method_exists($mockBuilder, 'addMethods')) {
            $mockBuilder->addMethods(['getHtmlId', 'getGroup']);
        } else {
            $mockBuilder->setMethods(['getHtmlId', 'getGroup']);
        }
        $mockBuilder->disableOriginalConstructor();
        $mockContainer = $mockBuilder->getMock();
        $mockContainer->method('getHtmlId')->willReturn($configPath['section'] . '_' . $configPath['group']);
        $mockContainer->method('getGroup')->willReturn(['path' => $configPath['section']]);

        $mockForm = $this->getMockBuilder(Form::class)->disableOriginalConstructor()->getMock();

        $field = $this->objectManager->get($fieldType);
        $field->setForm($mockForm);
        $field->setHtmlId($configPath['section'] . '_' . $configPath['group'] . '_' . $configPath['field']);
        $field->setContainer($mockContainer);
        $field->setData('disabled', false);
        $field->setData('can_use_default_value', true);
        $field->setData('can_use_website_value', true);
        $field->setData('value', $fieldValue);

        return $field;
    }

    /**
     * @param bool $called
     * @param bool $returns
     *
     * @return GetFeaturesInterface|MockObject
     */
    private function createMockGetFeatures($called = true, $returns = true)
    {
        $expects = $called ? $this->any() : $this->never();

        $mockAccountFeatures = $this->getMockBuilder(AccountFeaturesInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountFeatures->expects($expects)
            ->method('isFeatureAvailable')
            ->willReturn($returns);
        $mockAccountFeatures->expects($expects)
            ->method('getUpgradeMessage')
            ->willReturn('Upgrade to use this feature');
        $mockAccountFeatures->expects($expects)
            ->method('getUpgradeUrl')
            ->willReturn('https://box.klevu.com/analytics/km');

        $mockGetFeatures = $this->getMockBuilder(GetFeaturesInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGetFeatures->expects($expects)->method('execute')->willReturn($mockAccountFeatures);

        return $mockGetFeatures;
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
     * @return array[]
     */
    private function getSystemConfigFormFieldPluginInfo()
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->get(PluginList::class);

        return $pluginList->get(Field::class, []);
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
