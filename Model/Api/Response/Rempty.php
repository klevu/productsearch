<?php

namespace Klevu\Search\Model\Api\Response;

class Rempty extends \Klevu\Search\Model\Api\Response
{

    public function _construct()
    {
        $this->successful = false;
        $this->addData([
            'message' => "No HTTP response received. if you are using PHP version 5.4, please make sure to enable the php_openssl.dll module in your php.ini file."
        ]);
    }

    /**
     * Override the parse response method, this API response is static.
     *
     * @param \Laminas\Http\Response $response
     *
     * @return $this
     */
    protected function parseRawResponse(\Laminas\Http\Response $response)
    {
        // Do nothing
        return $this;
    }
}
