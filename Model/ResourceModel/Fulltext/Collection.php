<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Klevu\Search\Model\ResourceModel\Fulltext;

use Magento\Framework\App\RequestInterface;

/**
 * Fulltext Collection
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection
{

    /**
     * Request instance
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    
    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function afterSetOrder(\Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $collection)
    {
        
        $config = \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config');
        if ($config->isLandingEnabled()==1 && $config->isExtensionConfigured()) {
            $session = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\Session\Generic');
            $collection_order = $this->request->getParam('product_list_order');
            $module_name = $this->request->getModuleName();
            if (empty($collection_order) && !empty($session->getData('ids')) && $module_name == "catalogsearch") {
                $collection->getSelect()->order(new \Zend_Db_Expr(sprintf('FIELD(`e`.`entity_id`, %s) ASC', implode(',', $session->getData('ids')))));
            } else {
                if ($collection_order == 'relevance' && !empty($session->getData('ids')) && $module_name == "catalogsearch") {
                    $collection->getSelect()->order(new \Zend_Db_Expr(sprintf('FIELD(`e`.`entity_id`, %s) ASC', implode(',', $session->getData('ids')))));
                }
            }
            return $collection;
        }
    }
    
    public function aroundSetCollection(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        \Closure $proceed,
        $collection
    ) {
        
        $result = $proceed($collection);
        $config = \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config');
        if ($config->isLandingEnabled()==1 && $config->isExtensionConfigured()) {
            $session = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\Session\Generic');
            $collection_order = $this->request->getParam('product_list_order');
            $module_name = $this->request->getModuleName();
            if (empty($collection_order) && !empty($session->getData('ids')) && $module_name == "catalogsearch") {
                $subject->getCollection()->getSelect()->order(new \Zend_Db_Expr(sprintf('FIELD(`e`.`entity_id`, %s) ASC', implode(',', $session->getData('ids')))));
            } else {
                if ($collection_order == 'relevance' && !empty($session->getData('ids')) && $module_name == "catalogsearch") {
                    $subject->getCollection()->getSelect()->order(new \Zend_Db_Expr(sprintf('FIELD(`e`.`entity_id`, %s) ASC', implode(',', $session->getData('ids')))));
                }
            }
            $result = $proceed($collection);
            return $result;
        }
    }
}
