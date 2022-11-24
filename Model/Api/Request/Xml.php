<?php

namespace Klevu\Search\Model\Api\Request;

use Klevu\Search\Model\Api\Request;
use Magento\Framework\App\ObjectManager;
use Zend\Http\Client;

class Xml extends Request
{
    /**
     * @return string
     */
    public function __toString()
    {
        $string = parent::__toString();
        return sprintf("%s\n%s\n", $string, $this->getDataAsXml());
    }

    /**
     * Convert the request data into an XML string.
     *
     * @return string
     */
    public function getDataAsXml()
    {
        $xml = new XMLExtended("<request/>");
        $this->_convertArrayToXml($this->getData(), $xml);
        return $xml->asXML();
    }

    /**
     * Add data to the request as XML content and set the Content-Type to application/xml.
     *
     * @return Client
     */
    protected function build()
    {
        $client = parent::build();
        $convertDataToXml = $this->getDataAsXml();
        $gZen = gzencode($convertDataToXml, 5); // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $requestHeaders  = $client->getRequest()->getHeaders();
        if ($gZen !== false) {
            $requestHeaders->addHeaders(["Content-Encoding" => "gzip"]);
            $requestHeaders->addHeaders(["Content-Type" => "application/xml"]);
            $client
                ->setHeaders($requestHeaders)
                ->setRawBody($gZen);
        } else {
            $requestHeaders->addHeaders(["Content-Type" => "application/xml"]);
            $client
                ->setHeaders($requestHeaders)
                ->setRawBody($convertDataToXml);
        }

        return $client;
    }

    /**
     * Convert the given array of data into a SimpleXMLElement. Uses array keys as XML element
     * names and values as element values, except for numeric keys where the element name gets
     * set to "item{numeric_key}" unless the value is an array in which case it gets added to
     * the parent XML element directly. Recursively descends into array values to convert them
     * into XML. For example:
     *
     * array(
     *     "sessionId" => "Klevu-ses-132123123123_123",
     *     "records" => array(
     *         0 => array(
     *             "record" => array(
     *                 "pairs" => array(
     *                     0 => array(
     *                         "pair" => array(
     *                             "key" => "id",
     *                             "value" => "1"
     *                         )
     *                     ),
     *                     1 => array(
     *                         "pair" => array(
     *                             "key" => "name",
     *                             "value" => "Test product"
     *                         )
     *                     )
     *                 )
     *             )
     *         ),
     *         1 => array(
     *             "record" => array(
     *                 "pairs" => array(
     *                     0 => array(
     *                         "pair" => array(
     *                             "key" => "id",
     *                             "value" => "1"
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * );
     *
     * will get converted to:
     *
     * <?xml version="1.0"?>
     * <request>
     *     <sessionId>Klevu-ses-132123123123_123</sessionId>
     *     <records>
     *         <record>
     *             <pairs>
     *                 <pair>
     *                     <key>id</key>
     *                     <value>1</value>
     *                 </pair>
     *                 <pair>
     *                     <key>name</key>
     *                     <value>Test product</value>
     *                 </pair>
     *             </pairs>
     *         </record>
     *         <record>
     *             <pairs>
     *                 <pair>
     *                     <key>id</key>
     *                     <value>1</value>
     *                 </pair>
     *             </pairs>
     *         </record>
     *     </records>
     * </request>
     *
     * @param array $array The data to convert.
     * @param XMLExtended $parent XML element used as a parent for the data.
     */
    protected function _convertArrayToXml(array $array, XMLExtended &$parent)
    {
        $flag = 0;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $this->_convertArrayToXml($value, $parent);
                } else {
                    $child = $parent->addChild($key);
                    $this->_convertArrayToXml($value, $child);
                }
            } else {
                $key = (is_numeric($key)) ? "item" . $key : $key;

                // Disabling checks on htmlspecialchars usage as alternatives using Escaper
                //  all prevent double encoding, which breaks sync where html entities are used
                //  eg, we need &trade; to be converted to &amp;trade;
                // phpcs:disable Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
                if ($value == "desc" || $value == "shortDesc") {
                    $flag =1;
                    $parent->addChild($key, $this->stripInvalidXml(htmlspecialchars((string)$value)));
                } else {
                    if ($flag == 1) {
                        $parent = $parent->addChild($key);
                        $parent->addCData($this->stripInvalidXml(htmlspecialchars((string)$value)));
                    } else {
                        $flag = 0;
                        $parent->addChild($key, $this->stripInvalidXml(htmlspecialchars((string)$value)));
                    }
                }
                // phpcs:enable Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
            }
        }
    }

    /**
     * Removes invalid XML
     *
     * @access public
     * @param string $value
     * @return string
     */
    public function stripInvalidXml($value)
    {
        if (is_array($value)) {
            return $value;
        }
        $ret = "";
        $current="";
        if (empty($value) && '0' !== $value && !is_int($value) && !is_float($value)) {
            return $ret;
        }

        $length = strlen((string)$value);
        for ($i=0; $i < $length; $i++) {
            $current = ord($value[$i]);
            if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF))) {
                $ret .= chr($current); // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            } else {
                $ret .= " ";
            }
        }
        return $ret;
    }
}
