<?php

namespace Klevu\Search\Model\Context;

use Klevu\Search\Model\Context\ApiKlevu as Klevu_Core_Api;


class Api extends AbstractContext
{
    /**
     *  context constructor.
     * @param ApiKlevu $klevuCoreApi
     * @param array $data
     */
    public function __construct(
        Klevu_Core_Api $klevuCoreApi,
        $data = []
    )
    {
        $dataNew = array(
            'klevu_core_api' => $klevuCoreApi
        );
		$data = $data + $dataNew;
        parent::__construct($data);
    }
}
