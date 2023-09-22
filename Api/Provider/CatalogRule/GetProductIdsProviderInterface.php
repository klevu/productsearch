<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Api\Provider\CatalogRule;

interface GetProductIdsProviderInterface
{
    /**
     * @return int[]
     */
    public function get();
}
