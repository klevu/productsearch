<?php

namespace Klevu\Search\Model\Category;

interface KlevuCategoryActionsInterface
{
    /**
     * @param array $data
     * @param mixed $response
     *
     * @return mixed
     */
    public function executeDeleteCategorySuccess(array $data, $response);

    /**
     * @param array $data
     * @param mixed $response
     *
     * @return mixed
     */
    public function executeUpdateCategorySuccess(array $data, $response);

    /**
     * @param array $data
     * @param mixed $response
     *
     * @return mixed
     */
    public function executeAddCategorySuccess(array $data, $response);
}
