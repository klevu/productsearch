<?php

namespace Klevu\Search\Model\Api\Response;

use Klevu\Search\Model\Api\Response;

class Message extends Response
{

    protected function parseRawResponse($response)
    {
        parent::parseRawResponse($response);

        if ($this->isSuccess()) {
            $xml = $this->getXml();

            if (isset($xml->status) && strtolower($xml->status) === "success") {
                $this->successful = true;
            } else {
                $this->successful = false;
            }

            if (isset($xml->msg)) {
                $this->setMessage((string) $xml->msg);
            }

            if (isset($xml->sessionId)) {
                $this->setSessionId((string) $xml->sessionId);
            }
        }

        return $this;
    }
}
