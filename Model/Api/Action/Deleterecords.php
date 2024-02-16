<?php

namespace Klevu\Search\Model\Api\Action;

class Deleterecords extends Addrecords
{
    const ENDPOINT = "/rest/service/deleteRecords";
    const METHOD   = "POST";
    const ACTION = "delete";

    /**
     * mandatory_field_name => allowed_empty
     *
     * @var bool[]
     */
    protected $mandatory_fields = [
        "id" => false
    ];
}
