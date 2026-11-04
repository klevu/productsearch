<?php

namespace Klevu\Search\Test\Unit\Etc;

use PHPUnit\Framework\TestCase;

/**
 * Product and order sync each call setJobCode() on Klevu\Search\Model\Sync.
 * When the instance was shared, the last one built won, so order saves scheduled klevu_search_order_sync
 * instead of klevu_search_product_sync. Each sync must get its own instance.
 */
class DiConfigTest extends TestCase
{
    /**
     * @dataProvider syncClassesDataProvider
     */
    public function testSyncModelIsNotSharedWithOtherSyncs($type)
    {
        $xpath = new \DOMXPath($this->loadDiConfig());
        $arguments = $xpath->query(sprintf(
            '/config/type[@name="%s"]/arguments/argument[@name="klevuSyncModel"]',
            $type
        ));

        $this->assertSame(1, $arguments->length, sprintf('%s has no klevuSyncModel argument in etc/di.xml', $type));
        /** @var \DOMElement $argument */
        $argument = $arguments->item(0);
        $this->assertSame('object', $argument->getAttribute('xsi:type'));
        $this->assertSame('false', $argument->getAttribute('shared'));
        $this->assertSame('Klevu\Search\Model\Sync', trim($argument->textContent));
    }

    /**
     * @return array[]
     */
    public function syncClassesDataProvider()
    {
        return [
            ['Klevu\Search\Model\Product\Sync'],
            ['Klevu\Search\Model\Order\Sync'],
        ];
    }

    /**
     * @return \DOMDocument
     */
    private function loadDiConfig()
    {
        $document = new \DOMDocument();
        $this->assertTrue($document->load(__DIR__ . '/../../../etc/di.xml'), 'etc/di.xml could not be loaded');

        return $document;
    }
}
