<?php

namespace Klevu\Search\Model\Context;

//session
use Klevu\Search\Model\Api\Action\Startsession as Klevu_Session_Start;
//user + webstore
use Klevu\Search\Model\Api\Action\Adduser as Klevu_User_Add;
use Klevu\Search\Model\Api\Action\Getuserdetail as Klevu_User_Get;
use Klevu\Search\Model\Api\Action\Checkuserdetail as Klevu_User_Check;
use Klevu\Search\Model\Api\Action\Addwebstore as Klevu_Webstore_Add;
// product
use Klevu\Search\Model\Api\Action\Addrecords as Klevu_Product_Add;
use Klevu\Search\Model\Api\Action\Deleterecords as Klevu_Product_Delete;
use Klevu\Search\Model\Api\Action\Updaterecords as Klevu_Product_Update;
//search
use Klevu\Search\Model\Api\Action\Idsearch as Klevu_Search_Id;
use Klevu\Search\Model\Api\Action\Producttracking as Klevu_Search_Track_Product;
use Klevu\Search\Model\Api\Action\Searchtermtracking as Klevu_Search_Track_Term;
// extra
use Klevu\Search\Model\Api\Action\Debuginfo as Klevu_Debug;
use Klevu\Search\Model\Api\Action\Features as Klevu_Features;
use Klevu\Search\Model\Api\Action\Getplans as Klevu_Plans;
use Klevu\Search\Model\Api\Action\Gettimezone as Klevu_Timezone;


class ApiKlevu extends AbstractContext
{
    /**
     *  context constructor.
     * @param array $data
     */
    public function __construct(
        Klevu_Session_Start $klevuSessionStart,
        Klevu_User_Add $klevuUserAdd,
        Klevu_User_Get $klevuUserGet,
        Klevu_User_Check $klevuUserCheck,
        Klevu_Webstore_Add $klevuWebstoreAdd,
        Klevu_Product_Add $klevuProductAdd,
        Klevu_Product_Delete $klevuProductDelete,
        Klevu_Product_Update $klevuProductUpdate,
        Klevu_Search_Id $klevuSearchId,
        Klevu_Search_Track_Product $klevuSearchTrackProduct,
        Klevu_Search_Track_Term $klevuSearchTrackTerm,
        Klevu_Debug $klevuDebug,
        Klevu_Features $klevuFeatures,
        Klevu_Plans $klevuPlans,
        Klevu_Timezone $klevuTimezone,
        $data = []
    )
    {
        $dataNew = array(
            'klevu_session_start' => $klevuSessionStart,
            'klevu_user_add' => $klevuUserAdd,
            'klevu_user_get' => $klevuUserGet,
            'klevu_user_check' => $klevuUserCheck,
            'klevu_webstore_add' => $klevuWebstoreAdd,
            'klevu_product_add' => $klevuProductAdd,
            'klevu_product_delete' => $klevuProductDelete,
            'klevu_product_update' => $klevuProductUpdate,
            'klevu_search_id' => $klevuSearchId,
            'klevu_search_track_product' => $klevuSearchTrackProduct,
            'klevu_search_track_term' => $klevuSearchTrackTerm,
            'klevu_debug' => $klevuDebug,
            'klevu_features' => $klevuFeatures,
            'klevu_plans' => $klevuPlans,
            'klevu_timezone' => $klevuTimezone,
            'klevu_data' => $data
        );
		$data = $data + $dataNew;
        parent::__construct($data);
    }


}
