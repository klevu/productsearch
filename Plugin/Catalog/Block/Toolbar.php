<?php
namespace Klevu\Search\Plugin\Catalog\Block;

class Toolbar
{

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

        if ($currentOrder) {
            if ($currentOrder == 'personalized') {
                $subject->getCollection()->getSelect()->order('search_result.score '. $direction);
            }
        }

        return $result;
    }

}