<?php

namespace Klevu\Search\Model;
use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;
use Klevu\Search\Model\Context\Client  as Klevu_Context_Client;
use Klevu\Search\Model\Context\Extended  as Klevu_Context_Extended;
use Klevu\Search\Model\Context\Api  as Klevu_Context_Api;
use Klevu\Search\Model\Context\AbstractContext as AbstractContext;


class ContextFE extends AbstractContext
{
    /**
     *  context constructor.
     * @param Klevu_HelperManager $helperManager
     * @param Klevu_StoreManagerInterface $storeManagerInterface
     * @param Klevu_Context_Client $klevuContextClient
     * @param Klevu_Context_Extended $klevuContextExtended
     * @param Klevu_Context_Api $klevuContextApi
     */
    public function __construct(
        Klevu_HelperManager $helperManager,
        Klevu_Context_Client $klevuContextClient,
        Klevu_Context_Extended $klevuContextExtended,
        Klevu_Context_Api $klevuContextApi

    )
    {
        $data = array(
            'helper_manager' => $helperManager,
            'klevu_context_client' => $klevuContextClient,
            'klevu_context_extended' => $klevuContextExtended,
            'klevu_context_api' => $klevuContextApi
        );
        $klevuContextClient->processOverrides($data);
        $klevuContextExtended->processOverrides($data);
        $klevuContextApi->processOverrides($data);
        parent::__construct($data);
    }


}
