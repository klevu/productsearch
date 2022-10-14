<?php

namespace Klevu\Search\Test\Integration\Ui\DataProvider\Product\Form\Modifier\Attributes;

use Klevu\Search\Model\Attribute\Rating;
use Klevu\Search\Ui\DataProvider\Product\Form\Modifier\Attributes\Rating as RatingModifier;

/**
 * @magentoDataFixture Magento/Catalog/_files/product_simple.php
 * @magentoDbIsolation enabled
 */
class RatingTest extends AbstractTestCase
{
    const META_GROUP_CODE = 'product-details';

    /**
     * @magentoAppArea adminhtml
     */
    public function testModifyMetaShowsReviewCountInputAtStoreLevel()
    {
        $this->setupPhp5();

        $expectedMeta = $this->getExpectedMeta(
            [],
            static::META_GROUP_CODE,
            Rating::ATTRIBUTE_CODE
        );

        $this->mockRequest->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn('1');

        $this->modifyAndAssert($expectedMeta);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testModifyMetaDisablesReviewCountInputAtStoreLevel()
    {
        $this->setupPhp5();

        $expectedMeta = $this->getExpectedMeta(
            ['disabled' => true],
            static::META_GROUP_CODE,
            Rating::ATTRIBUTE_CODE
        );

        $this->mockRequest->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn('1');

        $this->modifyAndAssert($expectedMeta);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testModifyMetaHidesReviewCountInputAtGlobalAndWebsiteLevel()
    {
        $this->setupPhp5();

        $expectedMeta = $this->getExpectedMeta(
            ['visible' => false],
            static::META_GROUP_CODE,
            Rating::ATTRIBUTE_CODE
        );

        $this->mockRequest->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn(null);

        $this->modifyAndAssert($expectedMeta);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     */
    public function testModifyMetaShowsReviewCountInputAtGlobalAndWebsiteLevelForSingleStoreMode()
    {
        $this->setupPhp5();

        $expectedMeta = $this->getExpectedMeta(
            [],
            static::META_GROUP_CODE,
            Rating::ATTRIBUTE_CODE
        );

        $this->mockRequest->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn(null);

        $this->modifyAndAssert($expectedMeta);
    }

    /**
     * @return void
     */
    protected function setupPhp5()
    {
        parent::setupPhp5();

        $this->mockRequest = $this->getMockRequest();
        $this->klevuModifier = $this->getRatingModifier();
        $this->attributeMeta = $this->getAttributeMeta();
    }

    /**
     * @return RatingModifier
     */
    private function getRatingModifier()
    {
        return $this->objectManager->create(RatingModifier::class, [
            'request' => $this->mockRequest
        ]);
    }

    /**
     * @return array
     */
    private function getAttributeMeta()
    {
        return [
            'code' => Rating::ATTRIBUTE_CODE,
            'componentType' => 'field',
            'dataType' => 'text',
            'formElement' => 'input',
            'globalScope' => false,
            'label' => 'Klevu Rating',
            'required' => '0',
            'scopeLabel' => '[STORE VIEW]',
            'sortOrder' => '__placeholder__',
            'source' => static::META_GROUP_CODE,
            'visible' => '1',
        ];
    }
}
