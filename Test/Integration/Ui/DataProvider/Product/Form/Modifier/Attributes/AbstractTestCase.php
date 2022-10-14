<?php

namespace Klevu\Search\Test\Integration\Ui\DataProvider\Product\Form\Modifier\Attributes;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav\CompositeConfigProcessor;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\Ui\DataProvider\Mapper\FormElement;
use Magento\Ui\DataProvider\Mapper\MetaProperties;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    protected $mockRequest;
    /**
     * @var ModifierInterface
     */
    protected $klevuModifier;
    /**
     * @var array
     */
    protected $attributeMeta = [];
    /**
     * @var ObjectManager
     */
    protected $objectManager;
    /**
     * @var LocatorInterface|MockObject
     */
    protected $locatorMock;
    /**
     * @var mixed
     */
    protected $eavModifier;

    protected function setupPhp5()
    {
        $mappings = [
            'text' => 'input',
            'hidden' => 'input',
            'boolean' => 'checkbox',
            'media_image' => 'image',
            'price' => 'input',
            'weight' => 'input',
            'gallery' => 'image'
        ];
        $this->objectManager = Bootstrap::getObjectManager();

        $this->locatorMock = $this->getMockForAbstractClass(LocatorInterface::class);
        $this->locatorMock->method('getStore')->willReturn(
            $this->objectManager->get(StoreInterface::class)
        );

        $params = [
            'locator' => $this->locatorMock,
            'formElementMapper' => $this->objectManager->create(FormElement::class, ['mappings' => $mappings])
        ];
        if (!$this->isVersionLessThan('2.3.5')) {
            $metaPropertiesMapper = $this->objectManager->create(MetaProperties::class, ['mappings' => []]);
            $compositeConfigProcessor = $this->objectManager->create(
                CompositeConfigProcessor::class,
                ['eavWysiwygDataProcessors' => []]
            );

            $params['metaPropertiesMapper'] = $metaPropertiesMapper;
            $params['wysiwygConfigProcessor'] = $compositeConfigProcessor;
        }

        $this->eavModifier = $this->objectManager->create(Eav::class, $params);
    }

    /**
     * @param array $expectedMeta
     *
     * @return void
     * @throws NoSuchEntityException
     */
    protected function modifyAndAssert(array $expectedMeta)
    {
        $product = $this->getProductInstance();
        $this->locatorMock->method('getProduct')->willReturn($product);

        $actualMeta = $this->eavModifier->modifyMeta([]);
        $actualMeta = $this->klevuModifier->modifyMeta($actualMeta);

        $this->prepareData($actualMeta, $expectedMeta);
        $this->assertEquals($expectedMeta, $actualMeta);
    }

    /**
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    protected function getProductInstance()
    {
        // copied from \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractEavTest::getProduct
        // which is not available pre M2.3.5
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        return $productRepository->get('simple', false, Store::DEFAULT_STORE_ID);
    }

    /**
     * @param array $data
     * @param array $expectedData
     *
     * @return void
     */
    private function prepareData(array &$data, array $expectedData)
    {
        // copied from \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractEavTest::prepareDataForComparison
        // which is not available pre M2.3.5
        foreach ($data as $key => &$item) {
            if (!isset($expectedData[$key])) {
                unset($data[$key]);
                continue;
            }
            if ($item instanceof Phrase) {
                $item = (string)$item;
            } elseif (is_array($item)) {
                $this->prepareData($item, $expectedData[$key]);
            } elseif ($key === 'price_id' || $key === 'sortOrder') {
                $data[$key] = '__placeholder__';
            }
        }
    }

    /**
     * @param array $data
     * @param string $groupCode
     * @param string $attributeCode
     *
     * @return array|array[]
     */
    protected function getExpectedMeta(array $data, $groupCode, $attributeCode)
    {
        $attributeMeta = array_merge($this->attributeMeta, $data);
        // copied from \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractEavTest::addMetaNesting
        // which is not available pre M2.3.5
        return [
            $groupCode => [
                'arguments' => ['data' => ['config' => ['dataScope' => 'data.product']]],
                'children' => [
                    'container_' . $attributeCode => [
                        'children' => [$attributeCode => ['arguments' => ['data' => ['config' => $attributeMeta]]]],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return RequestInterface|MockObject
     */
    protected function getMockRequest()
    {
        return $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    private function isVersionLessThan($version)
    {
        $productMetadataInterface = $this->objectManager->get(ProductMetadataInterface::class);

        return version_compare($productMetadataInterface->getVersion(), $version, '<') === true;
    }
}
