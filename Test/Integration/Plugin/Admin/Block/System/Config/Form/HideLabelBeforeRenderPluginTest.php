<?php

namespace Klevu\Search\Test\Integration\Plugin\Admin\Block\System\Config\Form;

use Klevu\Search\Plugin\Admin\System\Config\Form\Field\HideLabelForSingleStoreModePlugin;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\Label;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\App\State as AppAreaState;
use Magento\TestFramework\Interception\PluginList;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class HideLabelBeforeRenderPluginTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    private $pluginName = 'Klevu_Search::AdminFormFieldHideLabels';

    private $labelsToHide = [
        'klevu_search_add_to_cart_enabled_info' => 'klevu_search_add_to_cart_enabled_info',
        'klevu_search_attributes_info_attribute' => 'klevu_search_attributes_info_attribute',
        'klevu_search_cmscontent_enabledcmsfront_info' => 'klevu_search_cmscontent_enabledcmsfront_info',
        'klevu_search_secureurl_setting_info_enabled' => 'klevu_search_secureurl_setting_info_enabled',
        'klevu_search_image_setting_info_enabled' => 'klevu_search_image_setting_info_enabled'
    ];

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
        $this->assertSame(HideLabelForSingleStoreModePlugin::class, $pluginInfo[$this->pluginName]['instance']);

        $this->tearDownPhp5();
    }

    public function testBeforeRenderCanBeCalled()
    {
        $this->setupPhp5();

        $testFieldId = 'some_test_field';
        $subject = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $label = $this->objectManager->create(Label::class);
        $label->setId($testFieldId);

        $plugin = $this->createHideLabelsForSingleStoreModePlugin();

        $newElement = $plugin->beforeRender($subject, $label)[0];
        $data = $newElement->getData();

        if (method_exists($this, 'assertIsArray')) {
            $this->assertisArray($data);
        } else {
            $this->assertTrue(is_array($data), 'Is Array');
        }
        $this->assertSame($testFieldId, $newElement->getId());
        $this->assertSame('label', $newElement->getType());
    }

    /**
     * @dataProvider labelsToHideInSingleStoreMode
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     */
    public function testBeforeRenderRemovesLabelDataInSingleStoreMode($fieldToHide)
    {
        $this->setupPhp5();

        $mockSubject = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $label = $this->objectManager->create(Label::class);
        $label->setId($fieldToHide);

        $plugin = $this->createHideLabelsForSingleStoreModePlugin();

        $labelToRender = $plugin->beforeRender($mockSubject, $label)[0];
        $this->assertSame($fieldToHide, $labelToRender->getId());
        $this->assertCount(0, $labelToRender->getData());
    }

    /**
     * @dataProvider labelsToHideInSingleStoreMode
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     */
    public function testBeforeRenderDoesNotRemoveTextFieldData($fieldToHide)
    {
        $this->setupPhp5();

        $mockSubject = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $textField = $this->objectManager->create(Text::class);
        $textField->setId($fieldToHide);

        $plugin = $this->createHideLabelsForSingleStoreModePlugin();

        $labelToRender = $plugin->beforeRender($mockSubject, $textField)[0];
        $this->assertSame($fieldToHide, $labelToRender->getId());
        $this->assertNotCount(0, $labelToRender->getData());
    }

    /**
     * @dataProvider labelsToHideInSingleStoreMode
     * @magentoConfigFixture default/general/single_store_mode/enabled 0
     */
    public function testBeforeRenderDoesNotRemoveLabelDataInMultiStoreMode($fieldToHide)
    {
        $this->setupPhp5();

        $mockSubject = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $label = $this->objectManager->create(Label::class);
        $label->setId($fieldToHide);

        $plugin = $this->createHideLabelsForSingleStoreModePlugin();

        $labelToRender = $plugin->beforeRender($mockSubject, $label)[0];
        $this->assertSame($fieldToHide, $labelToRender->getId());
        $this->assertNotCount(0, $labelToRender->getData());
    }

    /**
     * @return string[][]
     */
    public function labelsToHideInSingleStoreMode()
    {
        return [
            ['klevu_search_add_to_cart_enabled_info'],
            ['klevu_search_attributes_info_attribute'],
            ['klevu_search_cmscontent_enabledcmsfront_info'],
            ['klevu_search_secureurl_setting_info_enabled'],
            ['klevu_search_image_setting_info_enabled']
        ];
    }

    /**
     * @return HideLabelForSingleStoreModePlugin
     */
    private function createHideLabelsForSingleStoreModePlugin()
    {
        $mockScopeConfig = $this->objectManager->get(ScopeConfigInterface::class);

        return $this->objectManager->create(HideLabelForSingleStoreModePlugin::class, [
            'scopeConfig' => $mockScopeConfig,
            'labelsToHide' => $this->labelsToHide
        ]);
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
