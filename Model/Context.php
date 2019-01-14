<?php

namespace Klevu\Search\Model;
use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;
use Klevu\Search\Model\Api\Action\Startsession as Klevu_Api_Action_Startsession;
use Magento\Backend\Model\Session as  Klevu_Backend_Session;
use Klevu\Search\Model\Sync as  Klevu_Sync;
use Magento\Framework\App\ResourceConnection as Klevu_ResourceConnection;
use Magento\Store\Model\StoreManagerInterface as Klevu_StoreManagerInterface;
use Magento\Framework\App\ProductMetadataInterface as Klevu_Product_Meta;
use Klevu\Search\Model\Api\Action\Deleterecords as Klevu_Product_Delete;
use Klevu\Search\Model\Api\Action\Updaterecords as Klevu_Product_Update;
use Klevu\Search\Model\Api\Action\Addrecords as Klevu_Product_Add;
use Klevu\Search\Model\Api\Action\Features  as Klevu_Action_Feature;
use Magento\Customer\Model\Group as Klevu_Customer_Group;
use Magento\Framework\DataObject;
use Klevu\Search\Model\Product\ProductIndividualInterface as  Klevu_Product_Individual;


class Context extends DataObject
{
    /**
     *  context constructor.
     * @param Klevu_HelperManager $helperManager
     * @param Klevu_Api_Action_Startsession $startSession
     * @param Klevu_Backend_Session $backendSession
     * @param Klevu_Sync $sync
     * @param Klevu_ResourceConnection $resourceConnection
     * @param Klevu_StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        Klevu_HelperManager $helperManager,
        Klevu_Api_Action_Startsession $startSession,
        Klevu_Backend_Session $backendSession,
        Klevu_Sync $sync,
        Klevu_ResourceConnection $resourceConnection,
        Klevu_StoreManagerInterface $storeManagerInterface,
        Klevu_Product_Meta $klevuProductMeta,
        Klevu_Product_Delete $klevuProductDelete,
        Klevu_Product_Update $klevuProductUpdate,
        Klevu_Product_Add $klevuProductAdd,
        Klevu_Customer_Group $klevuCustomerGroup,
        Klevu_Action_Feature $klevuActionFeature,
		Klevu_Product_Individual $klevuProductIndividual
    )
    {
        $data = array(
            'helper_manager' => $helperManager,
            'start_session' => $startSession,
            'backend_session' => $backendSession,
            'sync' => $sync,
            'resource_connection' => $resourceConnection,
            'store_manager_interface' => $storeManagerInterface,
            'klevu_product_meta' => $klevuProductMeta,
            'klevu_product_delete' => $klevuProductDelete,
            'klevu_product_update' => $klevuProductUpdate,
            'klevu_product_add' => $klevuProductAdd,
            'klevu_customer_group' => $klevuCustomerGroup,
            'klevu_action_feature' => $klevuActionFeature,
			'klevu_product_individual' => $klevuProductIndividual
        );
        parent::__construct($data);
    }


}
