<?php

namespace Klevu\Search\Model\Api\Response;

use Klevu\Search\Model\Api\Response;
use Magento\Framework\Exception\NoSuchEntityException;

class Data extends Response
{
    /**
     * @param mixed $response
     *
     * @return $this|Data
     * @throws NoSuchEntityException
     */
    protected function parseRawResponse($response)
    {
        parent::parseRawResponse($response);

        if ($this->isSuccess()) {
            $data = $this->xmlToArray($this->getXml());

            $this->successful = false;
            if (isset($data['response'])) {
                if (strtolower($data['response']) == 'success') {
                    $this->successful = true;
                }
                unset($data['response']);
            }

            foreach ($data as $key => $value) {
                $this->setData($this->sanitizeKey($key), $value);
            }
        }

        return $this;
    }

    /**
     * Convert XML to an array.
     *
     * @param \SimpleXMLElement $xml
     *
     * @return array
     */
    protected function xmlToArray(\SimpleXMLElement $xml)
    {
        return json_decode(json_encode($xml), true);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function sanitizeKey(string $key)
    {
        return strtolower(
            trim(
                preg_replace(
                    '/([A-Z]|[0-9]+)/',
                    "_$1",
                    $key,
                ),
                '_',
            ),
        );
    }
}
