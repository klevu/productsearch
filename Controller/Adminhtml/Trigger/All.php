<?php

namespace Klevu\Search\Controller\Adminhtml\Trigger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Model\Session;
use Klevu\Search\Helper\Config;
use Klevu\Search\Helper\Data;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DB\Ddl\TriggerFactory;
use Magento\Framework\Config\ConfigOptionsListConstants;



class All extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $_backendModelSession;

    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_frameworkEventManagerInterface;
    
    protected $_triggerFactory;

    public function __construct(\Magento\Backend\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface, 
        \Klevu\Search\Helper\Config $searchHelperConfig, 
        \Klevu\Search\Helper\Data $searchHelperData,
        \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory)
    {

        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_backendModelSession = $context->getSession();
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_frameworkEventManagerInterface = $context->getEventManager();
        $this->_triggerFactory = $triggerFactory;

        parent::__construct($context);
    }

    public function execute()
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
		$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$connection = $resource->getConnection();	
		/** @var \Magento\Framework\App\DeploymentConfig $config */
		$config = $objectManager->get('Magento\Framework\App\DeploymentConfig');
		$dbname = $config->get(
			ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT
			. '/' . ConfigOptionsListConstants::KEY_NAME
		);
		//Delete Data from table
        if ($this->_searchHelperConfig->getTriggerOptionsFlag() == "1") {
        		$connection->query("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_CPIP;");
                $connection->query("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_LSA;");
                $connection->query("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_CPP;");
                
                 /** @var \Magento\Framework\DB\Ddl\Trigger $trigger */
		        $trigger_cpip = $this->_triggerFactory->create()
		            ->setName("Update_KlevuProductSync_For_CPIP")
		            ->setTime(\Magento\Framework\DB\Ddl\Trigger::TIME_AFTER)
		            ->setEvent(\Magento\Framework\DB\Ddl\Trigger::EVENT_UPDATE)
		            ->setTable($resource->getTableName('catalog_product_index_price'));
		        $trigger_cpip->addStatement("IF NEW.price <> OLD.price || NEW.final_price <> OLD.final_price THEN
                IF (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".$dbname."' AND  table_name = '".$resource->getTableName('klevu_product_sync')."') <> 0 THEN
                UPDATE klevu_product_sync
                SET last_synced_at = '0000-00-00 00:00:00'
                WHERE product_id = NEW.entity_id;
                END IF ;
                END IF ;");
                $connection->createTrigger($trigger_cpip); 
                
                $trigger_lsa = $this->_triggerFactory->create()
		            ->setName("Update_KlevuProductSync_For_LSA")
		            ->setTime(\Magento\Framework\DB\Ddl\Trigger::TIME_AFTER)
		            ->setEvent(\Magento\Framework\DB\Ddl\Trigger::EVENT_UPDATE)
		            ->setTable($resource->getTableName('cataloginventory_stock_status'));
		        $trigger_lsa->addStatement("IF NEW.stock_status <> OLD.stock_status THEN
                IF (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".$dbname."' AND table_name = '".$resource->getTableName('klevu_product_sync')."') <> 0 THEN
                UPDATE klevu_product_sync
                SET last_synced_at = '0000-00-00 00:00:00'
                WHERE product_id = NEW.product_id;
                END IF ;
                END IF ;");
                $connection->createTrigger($trigger_lsa);
                
                $trigger_cpp = $this->_triggerFactory->create()
		            ->setName("Update_KlevuProductSync_For_CPP")
		            ->setTime(\Magento\Framework\DB\Ddl\Trigger::TIME_AFTER)
		            ->setEvent(\Magento\Framework\DB\Ddl\Trigger::EVENT_UPDATE)
		            ->setTable($resource->getTableName('catalogrule_product_price'));
		        $trigger_cpp->addStatement("IF NEW.rule_price <> OLD.rule_price THEN
                IF (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '".$dbname."' AND table_name = '".$resource->getTableName('klevu_product_sync')."') <> 0 THEN
                UPDATE klevu_product_sync
                SET last_synced_at = '0000-00-00 00:00:00'
                WHERE product_id = NEW.product_id;
                END IF ;
                END IF ;");
                $connection->createTrigger($trigger_cpp);
                
                $this->messageManager->addSuccess(__("Trigger is activated."));  
        } else {
				$connection->query("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_CPIP;");
                $connection->query("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_LSA;");
                $connection->query("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_CPP;");
                $this->messageManager->addSuccess(__("Trigger is deactivated."));		
		}
        return $this->_redirect($this->_redirect->getRefererUrl());
    }
    
    protected function _isAllowed()
    {
        return true;
    }
    

}
