<?php

namespace Klevu\Search\Model\Api\Action;

class Updaterecords extends Addrecords
{
    const ENDPOINT = "/rest/service/updateRecords";
    const METHOD   = "POST";
    const ACTION = "update";

    /**
     * mandatory_field_name => allowed_empty
     *
     * @var bool[]
     */
    protected $mandatory_fields = [
        "id" => false
    ];
}
