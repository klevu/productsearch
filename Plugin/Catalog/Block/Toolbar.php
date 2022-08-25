<?php

namespace Klevu\Search\Plugin\Catalog\Block;

use Closure;
use Magento\Catalog\Block\Product\ProductList\Toolbar as ToolbarBlock;
use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;
use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Registry;
use Klevu\Search\Helper\Config as KlevuConfig;
use Magento\Framework\App\ProductMetadataInterface;

class Toolbar
{
    /**
     * Registry instance
     */
    private $registry;
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;
    /**
     * @var ToolbarModel
     */
    private $toolbarModel;
    /**
     * @var KlevuConfig
     */
    private $klevuconfig;

    /**
     * @param Registry $registry
     * @param ToolbarModel $toolbarModel
     * @param KlevuConfig $klevuconfig
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        Registry $registry,
        ToolbarModel $toolbarModel,
        KlevuConfig $klevuconfig,
        ProductMetadataInterface $productMetadata
    ) {
        $this->registry = $registry;
        $this->toolbarModel = $toolbarModel;
        $this->klevuconfig = $klevuconfig;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Plugin
     *
     * @param ToolbarBlock $subject
     * @param Closure $proceed
     * @param DataCollection $collection
     *
     * @return ToolbarBlock
     */
    public function aroundSetCollection(
        ToolbarBlock $subject,
        Closure $proceed,
        $collection
    ) {
        $direction = $subject->getCurrentDirection();
        $result = $proceed($collection);
        $currentEngine = $this->klevuconfig->getCurrentEngine();
        $version = $this->productMetadata->getVersion();
        if ($subject->getCurrentOrder() === 'personalized' &&
            ($currentEngine === "mysql" || version_compare($version, '2.3.1', '<=') === true)
        ) {
            $collection = $subject->getCollection();
            if ($collection) {
                $collection->getSelect()->order('search_result.score ' . $direction);
            }
        }

        return $result;
    }

    /**
     * @param ToolbarBlock $subject
     * @param string $direction
     *
     * @return string
     */
    public function afterGetCurrentDirection(ToolbarBlock $subject, $direction)
    {
        $currentEngine = $this->klevuconfig->getCurrentEngine();
        $version = $this->productMetadata->getVersion();
        if ($subject->getCurrentOrder() === 'personalized' &&
            ($currentEngine === "mysql" || version_compare($version, '2.3.1', '<=') === true)
        ) {
            $defaultDir = 'desc';
            $subject->setDefaultDirection($defaultDir);
            if (!$this->toolbarModel->getDirection()) {
                $direction = $defaultDir;
            }
        }

        return $direction;
    }
}