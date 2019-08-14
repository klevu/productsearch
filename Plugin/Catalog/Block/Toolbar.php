<?php
namespace Klevu\Search\Plugin\Catalog\Block;

use Magento\CatalogSearch\Model\ResourceModel\EngineInterface as EngineInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface as ScopeInterface;
use Klevu\Search\Helper\Config as KlevuConfig;
use \Magento\Framework\App\ProductMetadataInterface as ProductMetadataInterface;

class Toolbar
{

    /**
     * Registry instance
     */
    private $registry;
    private $productMetadata;
    private $toolbarModel;
    private $klevuconfig;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry,\Magento\Catalog\Model\Product\ProductList\Toolbar $toolbarModel,KlevuConfig $klevuconfig,ProductMetadataInterface $productMetadata)
    {

        $this->registry = $registry;
        $this->toolbarModel = $toolbarModel;
        $this->klevuconfig = $klevuconfig;
        $this->productMetadata = $productMetadata;
    }
    /**
     * Plugin
     *
     * @param \Magento\Catalog\Block\Product\ProductList\Toolbar $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Data\Collection $collection
     * @return \Magento\Catalog\Block\Product\ProductList\Toolbar
     */
    public function aroundSetCollection(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        \Closure $proceed,
        $collection
    ) {
        $currentOrder = $subject->getCurrentOrder();
        $direction = $subject->getCurrentDirection();
        $result = $proceed($collection);
        $currentEngine = $this->klevuconfig->getCurrentEngine();
        $version = $this->productMetadata->getVersion();
        if($currentEngine == "mysql" || version_compare($version, '2.3.1', '<=') === true ) {
            if ($currentOrder) {
                if ($currentOrder == 'personalized') {
                    $subject->getCollection()->getSelect()->order('search_result.score ' . $direction);
                }
            }

        }
        return $result;

    }


    public function afterGetCurrentDirection($subject, $dir)
    {
        $currentEngine = $this->klevuconfig->getCurrentEngine();
        $version = $this->productMetadata->getVersion();
        if($currentEngine == "mysql" || version_compare($version, '2.3.1', '<=') === true) {
            $currentOrder = $subject->getCurrentOrder();
            if ($currentOrder) {
                if ($currentOrder == 'personalized') {
                    $defaultDir = 'desc';
                    $subject->setDefaultDirection($defaultDir);
                    if (!$this->toolbarModel->getDirection()) {
                        $dir = $defaultDir;
                    }
                }
            }
        }
        return $dir;
    }
}