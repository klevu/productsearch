<?php

namespace Klevu\Search\Model\Api\Response;

use Magento\Framework\Exception\NoSuchEntityException;

class Timezone extends Data
{
    /**
     * @param $response
     *
     * @return void
     * @throws NoSuchEntityException
     */
    protected function parseRawResponse($response)
    {
        parent::parseRawResponse($response);

        // Timezone responses don't have a status parameters, just data
        // So the presence of the data is the status
        if ($this->hasData('timezone')) {
            $this->successful = true;
        } else {
            $this->successful = false;
        }
    }
}
