<?php

namespace Klevu\Search\Model\Api\Request;

class Post extends \Klevu\Search\Model\Api\Request
{
    /**
     * @var string[]
     */
    private $maskFields = array('restApiKey', 'email', 'password', 'Authorization');

    public function __toString()
    {
        $string = parent::__toString();

        $parameters = $this->getData();
        if (!empty($parameters)) {
            array_walk($parameters, function (&$value, $key) {
                if (in_array($key, $this->maskFields)) {
                    $value = sprintf("%s: %s", $key, '***************');
                } else {
                    $value = sprintf("%s: %s", $key, $value);
                }
            });
        }

        return sprintf("%s\nPOST parameters:\n%s\n", $string, implode("\n", $parameters));
    }

    /**
     * Add POST parameters to the request, force POST method.
     *
     * @return \Laminas\Http\Client
     */
    protected function build()
    {
        $client = parent::build();

        $client
            ->setMethod(\Laminas\Http\Request::METHOD_POST)
            ->setParameterPost($this->getData());

        return $client;
    }
}
