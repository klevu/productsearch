<?php

namespace Klevu\Search\Api\Service\Catalog\Product;

interface UpdateRatingsAttributesInterface
{
    /**
     * @param array $ratings
     *
     * @return void
     */
    public function execute(array $ratings);
}
