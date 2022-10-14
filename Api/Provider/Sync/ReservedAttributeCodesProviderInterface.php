<?php

namespace Klevu\Search\Api\Provider\Sync;

/**
 * Reserved attribute codes are not permitted to be included in other or otherAttributeToIndex
 *  fields when data synchronisation occurs
 */
interface ReservedAttributeCodesProviderInterface
{
    /**
     * @return string[]
     */
    public function execute();
}
