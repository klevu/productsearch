<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Plugin\Api\Search\SearchResult;

use Magento\Elasticsearch\SearchAdapter\DocumentFactory;
use Magento\Framework\Api\Search\SearchResult as FrameworkSearchResult;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class BeforeSetItemsTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function test_SearchResults_ReturnsEmptyArray_WhenNoResults()
    {
        $this->setupPhp5();
        $registry = $this->objectManager->get(Registry::class);
        $registry->unregister('current_order');
        $registry->register('current_order', 'personalized');
        $registry->unregister('search_ids');
        $registry->register('search_ids', [3, 1, 4, 2]);

        $items = [];

        $searchResult = $this->objectManager->get(FrameworkSearchResult::class);
        $searchResult->setItems($items);

        $result = $searchResult->getItems();
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_SearchResults_AreNotReordered_WhenSearchIdsAreNotSet()
    {
        $this->setupPhp5();
        $registry = $this->objectManager->get(Registry::class);
        $registry->unregister('current_order');
        $registry->register('current_order', 'personalized');
        $registry->unregister('search_ids');

        $documentFactory = $this->objectManager->get(DocumentFactory::class);
        $documents = [
            [
                '_id' => 3,
                '_score' => 100.98,
            ],
            [
                '_id' => 1,
                '_score' => 100.12,
            ],
            [
                '_id' => 4,
                '_score' => 50.54,
            ],
            [
                '_id' => 2,
                '_score' => 10.10,
            ],
        ];
        $items = [];
        foreach ($documents as $document) {
            $items[] = $documentFactory->create($document);
        }

        $searchResult = $this->objectManager->get(FrameworkSearchResult::class);
        $searchResult->setItems($items);

        $result = $searchResult->getItems();
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $keys = array_keys($result);
        $this->assertSame(3, $result[$keys[0]]->getId());
        $this->assertSame(1, $result[$keys[1]]->getId());
        $this->assertSame(4, $result[$keys[2]]->getId());
        $this->assertSame(2, $result[$keys[3]]->getId());
    }

    public function test_SearchResults_AreNotReordered_WhenOrderIsNotPersonalization()
    {
        $this->setupPhp5();
        $registry = $this->objectManager->get(Registry::class);
        $registry->unregister('current_order');
        $registry->unregister('search_ids');
        $registry->register('search_ids', [1, 4, 2, 3]);

        $documentFactory = $this->objectManager->get(DocumentFactory::class);
        $documents = [
            [
                '_id' => 3,
                '_score' => 100.98,
            ],
            [
                '_id' => 1,
                '_score' => 100.12,
            ],
            [
                '_id' => 4,
                '_score' => 50.54,
            ],
            [
                '_id' => 2,
                '_score' => 10.10,
            ],
        ];
        $items = [];
        foreach ($documents as $document) {
            $items[] = $documentFactory->create($document);
        }

        $searchResult = $this->objectManager->get(FrameworkSearchResult::class);
        $searchResult->setItems($items);

        $result = $searchResult->getItems();
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $keys = array_keys($result);
        $this->assertSame(3, $result[$keys[0]]->getId());
        $this->assertSame(1, $result[$keys[1]]->getId());
        $this->assertSame(4, $result[$keys[2]]->getId());
        $this->assertSame(2, $result[$keys[3]]->getId());
    }

    public function test_SearchResults_AreReorderedForPersonalization()
    {
        $this->setupPhp5();
        $registry = $this->objectManager->get(Registry::class);
        $registry->unregister('current_order');
        $registry->register('current_order', 'personalized');
        $registry->unregister('search_ids');
        $registry->register('search_ids', [1, 4, 2, 3]);

        $documentFactory = $this->objectManager->get(DocumentFactory::class);
        $documents = [
            [
                '_id' => 3,
                '_score' => 100.98,
            ],
            [
                '_id' => 1,
                '_score' => 100.12,
            ],
            [
                '_id' => 4,
                '_score' => 50.54,
            ],
            [
                '_id' => 2,
                '_score' => 10.10,
            ],
        ];
        $items = [];
        foreach ($documents as $document) {
            $items[] = $documentFactory->create($document);
        }

        $searchResult = $this->objectManager->get(FrameworkSearchResult::class);
        $searchResult->setItems($items);

        $result = $searchResult->getItems();
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $keys = array_keys($result);
        $this->assertSame(1, $result[$keys[0]]->getId());
        $this->assertSame(4, $result[$keys[1]]->getId());
        $this->assertSame(2, $result[$keys[2]]->getId());
        $this->assertSame(3, $result[$keys[3]]->getId());
    }

    public function test_SearchResults_AreReorderedForPersonalization_WhenIdsAreFloat()
    {
        $this->setupPhp5();
        $registry = $this->objectManager->get(Registry::class);
        $registry->unregister('current_order');
        $registry->register('current_order', 'personalized');
        $registry->unregister('search_ids');
        $registry->register('search_ids', [1.0, 4.5, 2.1, 3.0]);

        $documentFactory = $this->objectManager->get(DocumentFactory::class);
        $documents = [
            [
                '_id' => 3,
                '_score' => 100.98,
            ],
            [
                '_id' => 1,
                '_score' => 100.12,
            ],
            [
                '_id' => 4,
                '_score' => 50.54,
            ],
            [
                '_id' => 2,
                '_score' => 10.10,
            ],
        ];
        $items = [];
        foreach ($documents as $document) {
            $items[] = $documentFactory->create($document);
        }

        $searchResult = $this->objectManager->get(FrameworkSearchResult::class);
        $searchResult->setItems($items);

        $result = $searchResult->getItems();
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $keys = array_keys($result);
        $this->assertSame(1, $result[$keys[0]]->getId());
        $this->assertSame(4, $result[$keys[1]]->getId());
        $this->assertSame(2, $result[$keys[2]]->getId());
        $this->assertSame(3, $result[$keys[3]]->getId());
    }

    public function test_SearchResults_AreBatched_WhenSizeAndFromSetInRegistry()
    {
        $this->setupPhp5();
        $registry = $this->objectManager->get(Registry::class);
        $registry->unregister('current_order');
        $registry->register('current_order', 'personalized');
        $registry->unregister('search_ids');
        $registry->register('search_ids', [1, 4, 2, 3]);
        $registry->unregister('from');
        $registry->register('from', 2); // start item number
        $registry->unregister('size');
        $registry->register('size', 2); // number of items per page

        $documentFactory = $this->objectManager->get(DocumentFactory::class);
        $documents = [
            [
                '_id' => 3,
                '_score' => 100.98,
            ],
            [
                '_id' => 1,
                '_score' => 100.12,
            ],
            [
                '_id' => 4,
                '_score' => 50.54,
            ],
            [
                '_id' => 2,
                '_score' => 10.10,
            ],
        ];
        $items = [];
        foreach ($documents as $document) {
            $items[] = $documentFactory->create($document);
        }

        $searchResult = $this->objectManager->get(FrameworkSearchResult::class);
        $searchResult->setItems($items);

        $result = $searchResult->getItems();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $keys = array_keys($result);
        $this->assertSame(2, $result[$keys[0]]->getId());
        $this->assertSame(3, $result[$keys[1]]->getId());
    }

    public function test_SearchResults_AreBatched_WhenFromNotSetInRegistry()
    {
        $this->setupPhp5();
        $registry = $this->objectManager->get(Registry::class);
        $registry->unregister('current_order');
        $registry->register('current_order', 'personalized');
        $registry->unregister('search_ids');
        $registry->register('search_ids', [1, 4, 2, 3]);
        $registry->unregister('from');// start item number
        $registry->unregister('size');
        $registry->register('size', 2); // number of items per page

        $documentFactory = $this->objectManager->get(DocumentFactory::class);
        $documents = [
            [
                '_id' => 3,
                '_score' => 100.98,
            ],
            [
                '_id' => 1,
                '_score' => 100.12,
            ],
            [
                '_id' => 4,
                '_score' => 50.54,
            ],
            [
                '_id' => 2,
                '_score' => 10.10,
            ],
        ];
        $items = [];
        foreach ($documents as $document) {
            $items[] = $documentFactory->create($document);
        }

        $searchResult = $this->objectManager->get(FrameworkSearchResult::class);
        $searchResult->setItems($items);

        $result = $searchResult->getItems();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $keys = array_keys($result);
        $this->assertSame(1, $result[$keys[0]]->getId());
        $this->assertSame(4, $result[$keys[1]]->getId());
    }

    public function test_SearchResults_AreNotBatched_WhenSizeNotSetInRegistry()
    {
        $this->setupPhp5();
        $registry = $this->objectManager->get(Registry::class);
        $registry->unregister('current_order');
        $registry->register('current_order', 'personalized');
        $registry->unregister('search_ids');
        $registry->register('search_ids', [1, 4, 2, 3]);
        $registry->unregister('from');
        $registry->register('from', 2); // start item number
        $registry->unregister('size'); // number of items per page

        $documentFactory = $this->objectManager->get(DocumentFactory::class);
        $documents = [
            [
                '_id' => 3,
                '_score' => 100.98,
            ],
            [
                '_id' => 1,
                '_score' => 100.12,
            ],
            [
                '_id' => 4,
                '_score' => 50.54,
            ],
            [
                '_id' => 2,
                '_score' => 10.10,
            ],
        ];
        $items = [];
        foreach ($documents as $document) {
            $items[] = $documentFactory->create($document);
        }

        $searchResult = $this->objectManager->get(FrameworkSearchResult::class);
        $searchResult->setItems($items);

        $result = $searchResult->getItems();
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $keys = array_keys($result);
        $this->assertSame(1, $result[$keys[0]]->getId());
        $this->assertSame(4, $result[$keys[1]]->getId());
        $this->assertSame(2, $result[$keys[2]]->getId());
        $this->assertSame(3, $result[$keys[3]]->getId());
    }

    public function test_SearchResults_AreBatched_WhenPageNumberCalculationResultsInFloat()
    {
        $this->setupPhp5();
        $registry = $this->objectManager->get(Registry::class);
        $registry->unregister('current_order');
        $registry->register('current_order', 'personalized');
        $registry->unregister('search_ids');
        $registry->register('search_ids', [3.0, 1.0, 4.0, 2.0, 10.0, 8.0, 6.0, 5.0]); // not all items present
        $registry->unregister('from');
        $registry->register('from', 8); // start item number
        $registry->unregister('size');
        $registry->register('size', 5); // number of items per page

        $documentFactory = $this->objectManager->get(DocumentFactory::class);
        // at this point documents should be returned from ES orders by score.
        // checking that we order correctly if they come back in the wrong order
        $documents = [
            [
                '_id' => 1,
                '_score' => 100.12,
            ],
            [
                '_id' => 2,
                '_score' => 10.10,
            ],
            [
                '_id' => 3,
                '_score' => 100.98,
            ],
            [
                '_id' => 4,
                '_score' => 50.54,
            ],
            [
                '_id' => 5,
                '_score' => 15.2,
            ],
            [
                '_id' => 6,
                '_score' => 20,
            ],
            [
                '_id' => 7,
                '_score' => 30,
            ],
            [
                '_id' => 8,
                '_score' => 40.25,
            ],
            [
                '_id' => 9,
                '_score' => 51.23,
            ],
            [
                '_id' => 10,
                '_score' => 75,
            ],
        ];
        $items = [];
        foreach ($documents as $document) {
            $items[] = $documentFactory->create($document);
        }

        $searchResult = $this->objectManager->get(FrameworkSearchResult::class);
        $searchResult->setItems($items);

        $result = $searchResult->getItems();
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $keys = array_keys($result);
        $this->assertSame(8, $result[$keys[0]]->getId());
        $this->assertSame(6, $result[$keys[1]]->getId());
        $this->assertSame(5, $result[$keys[2]]->getId());
        $this->assertSame(9, $result[$keys[3]]->getId());
        $this->assertSame(7, $result[$keys[4]]->getId());
    }
}
