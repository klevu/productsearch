<?php

namespace Klevu\Search\Test\Integration\Block\Adminhtml\Form;

use Klevu\Search\Block\Adminhtml\Form\Field\Integration\Instructions;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 */
class IntegrationInstructionsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @dataProvider scopesToHideDataProvider
     */
    public function testReturnsNothingInDefaultAndWebSiteScope($scope)
    {
        $this->setUpPhp5();
        $configPath = [
            'section' => 'klevu_integration',
            'group' => 'authentication_keys',
            'field' => 'integration_instructions'
        ];

        $field = $this->createField($configPath);
        $store = $this->getStore();
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams([$scope => $store->getId()]);

        $block = $this->objectManager->get(Instructions::class);
        $this->assertSame('', $block->render($field));

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key 0
     */
    public function testReturnsTextInStoreScope()
    {
        $this->setUpPhp5();
        $configPath = [
            'section' => 'klevu_integration',
            'group' => 'authentication_keys',
            'field' => 'integration_instructions'
        ];

        $field = $this->createField($configPath);
        $store = $this->getStore();
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['store' => $store->getId()]);

        $block = $this->objectManager->get(Instructions::class);
        $this->assertNotSame('', $block->render($field));

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-123456789
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key abcdef123456789
     */
    public function testReturnsNothingInStoreScopeIfAlreadyIntegrated()
    {
        $this->setUpPhp5();
        $configPath = [
            'section' => 'klevu_integration',
            'group' => 'authentication_keys',
            'field' => 'integration_instructions'
        ];

        $field = $this->createField($configPath);
        $store = $this->getStore();
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['store' => $store->getId()]);

        $block = $this->objectManager->get(Instructions::class);
        $this->assertSame('', $block->render($field));

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return \string[][]
     */
    public function scopesToHideDataProvider()
    {
        return [
            ['default'],
            ['website']
        ];
    }

    /**
     * @param array $configPath e.g ['section' => 'klevu_search', 'group' => 'general' , 'field' => 'enable']
     * @param string $fieldType
     *
     * @return AbstractElement
     */
    private function createField(array $configPath, $fieldType = Text::class)
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
        $mockContainer->method('getGroup')->willReturn(['path' => $configPath['group']]);

        $mockForm = $this->getMockBuilder(Form::class)->disableOriginalConstructor()->getMock();

        $field = $this->objectManager->get($fieldType);
        $field->setForm($mockForm);
        $field->setHtmlId($configPath['section'] . '_' . $configPath['group'] . '_' . $configPath['field']);
        $field->setContainer($mockContainer);
        $field->setData('disabled', false);
        $field->setExpanded(1);

        return $field;
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode = 'klevu_test_store_1')
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @return void
     * @todo Move to tearDown when PHP 5.x is no longer supported
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }
}
