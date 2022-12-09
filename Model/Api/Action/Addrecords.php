<?php

namespace Klevu\Search\Model\Api\Action;

use Klevu\Logger\Constants as LoggerConstants;
use Magento\Store\Api\Data\StoreInterface;

class Addrecords extends \Klevu\Search\Model\Api\Actionall
{
    const ENDPOINT = "/rest/service/addRecords";
    const METHOD = "POST";
    const DEFAULT_REQUEST_MODEL = \Klevu\Search\Model\Api\Request\Xml::class;
    const DEFAULT_RESPONSE_MODEL = \Klevu\Search\Model\Api\Response\Message::class;

    /**
     * @var \Klevu\Search\Model\Api\Response\Invalid
     */
    protected $_apiResponseInvalid;

    /**
     * @var \Klevu\Search\Helper\Api
     */
    protected $_searchHelperApi;

    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * mandatory_field_name => allowed_empty
     * @var array
     */
    protected $mandatory_fields = [
        "id" => false,
        "name" => false,
        "url" => false,
        "salePrice" => false,
        "currency" => false,
        "category" => true,
        "listCategory" => true
    ];

    /**
     * @param \Klevu\Search\Model\Api\Response\Invalid $apiResponseInvalid
     * @param \Klevu\Search\Helper\Api $searchHelperApi
     * @param \Klevu\Search\Helper\Config $searchHelperConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface
     * @param \Klevu\Search\Helper\Data $searchHelperData
     */
    public function __construct(
        \Klevu\Search\Model\Api\Response\Invalid $apiResponseInvalid,
        \Klevu\Search\Helper\Api $searchHelperApi,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Klevu\Search\Helper\Data $searchHelperData
    ) {
        $this->_apiResponseInvalid = $apiResponseInvalid;
        $this->_searchHelperApi = $searchHelperApi;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_searchHelperData = $searchHelperData;
    }

    /**
     * @param array $parameters
     *
     * @return \Klevu\Search\Model\Api\Response|\Klevu\Search\Model\Api\Response\Rempty
     * @throws \Exception
     */
    public function execute($parameters = [])
    {
        $response = $this->getResponse();

        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return $this->_apiResponseInvalid->setErrors($validation_result);
        }

        $skipped_records = $this->validateRecords($parameters);
        if (count($parameters['records']) > 0) {
            if ($skipped_records != null) {
                // Validation has removed some of the records due to errors, but the rest
                // can still be submitted, so just log and proceed
                $response->setData("skipped_records", $skipped_records);
            }
        } else {
            return $this->_apiResponseInvalid->setErrors([
                "all_records_invalid" => implode(", ", $skipped_records["messages"])
            ]);
        }

        $this->prepareParameters($parameters);
        $endpoint = $this->buildEndpoint(
            static::ENDPOINT,
            $this->getStore(),
            $this->_searchHelperConfig->getRestHostname($this->getStore())
        );
        $request = $this->getRequest();
        $request
            ->setResponseModel($response)
            ->setEndpoint($endpoint)
            ->setMethod(static::METHOD)
            ->setData($parameters);

        return $request->send();
    }

    /**
     * Get the store used for this request.
     * @return StoreInterface
     */
    public function getStore()
    {
        if (!$this->hasData('store')) {
            $this->setData('store', $this->_storeModelStoreManagerInterface->getStore());
        }

        return $this->getData('store');
    }

    /**
     * @param array $parameters
     *
     * @return array|bool
     */
    protected function validate($parameters)
    {
        $errors = [];

        if (!isset($parameters['sessionId']) || empty($parameters['sessionId'])) {
            $errors['sessionId'] = "Missing session ID";
        }

        if (!isset($parameters['records']) || !is_array($parameters['records']) || count($parameters['records']) == 0) {
            $errors['records'] = "No records";
        }

        if (count($errors) == 0) {
            return true;
        }

        return $errors;
    }

    /**
     * Validate the records parameter and remove records that are invalid. Modifies
     * the $parameters argument in place. Return the list of skipped records and
     * their error messages.
     *
     * @param array $parameters
     *
     * @return array
     */
    protected function validateRecords(&$parameters)
    {
        if (isset($parameters['records']) && is_array($parameters['records'])) {
            $skipped_records = [
                "index" => [],
                "messages" => []
            ];

            foreach ($parameters['records'] as $i => $record) {
                $missing_fields = [];
                $empty_fields = [];

                foreach ($this->mandatory_fields as $mandatory_field => $allowed_empty) {
                    if (!array_key_exists($mandatory_field, $record)) {
                        $missing_fields[] = $mandatory_field;
                    } else {
                        if (!$allowed_empty &&
                            !is_numeric($record[$mandatory_field]) &&
                            empty($record[$mandatory_field])
                        ) {
                            $empty_fields[] = $mandatory_field;
                        }
                    }
                }

                $id = (isset($record['id']) && !empty($record['id'])) ? sprintf(" (id: %d)", $record['id']) : "";

                if (count($missing_fields) > 0 || count($empty_fields) > 0) {
                    unset($parameters["records"][$i]);
                    $skipped_records["index"][] = $i;
                    if (count($missing_fields) > 0) {
                        $skipped_records["messages"][] = sprintf(
                            "Record %d%s is missing mandatory fields: %s",
                            $i,
                            $id,
                            implode(", ", $missing_fields)
                        );
                    }
                    if (count($empty_fields) > 0) {
                        $skipped_records["messages"][] = sprintf(
                            "Record %d%s has empty mandatory fields: %s",
                            $i,
                            $id,
                            implode(", ", $empty_fields)
                        );
                    }
                }
            }

            return $skipped_records;
        }
    }

    /**
     * Convert the given parameters to a format expected by the XML request model.
     *
     * @param array $parameters
     */
    protected function prepareParameters(&$parameters)
    {
        foreach ($parameters['records'] as &$record) {
            if (isset($record['listCategory']) && is_array($record['listCategory'])) {
                $record['listCategory'] = implode(";;", $record['listCategory']);
            }

            if (isset($record['other']) && is_array($record['other'])) {
                $this->prepareOtherParameters($record);
            }

            if (isset($record['otherAttributeToIndex']) && is_array($record['otherAttributeToIndex'])) {
                $this->prepareOtherAttributeToIndexParameters($record);
            }

            if (isset($record['groupPrices']) && is_array($record['groupPrices'])) {
                $this->prepareGroupPricesParameters($record);
            }

            $pairs = [];

            foreach ($record as $key => $value) {
                $pairs[] = [
                    'pair' => [
                        'key' => $key,
                        'value' => $value
                    ]
                ];
            }

            $record = [
                'record' => [
                    'pairs' => $pairs
                ]
            ];
        }
    }

    /**
     * Flattens other parameters array to a string formatted: key:value[,value]
     *
     * @param array $record
     */
    protected function prepareOtherParameters(&$record)
    {
        foreach ($record['other'] as $key => &$value) {
            $key = $this->sanitiseOtherAttribute($key);
            if (is_array($value)) {
                if (isset($value['label'])) {
                    $label = $this->sanitiseOtherAttribute($value['label']);
                }
                if (isset($value['values'])) {
                    $value = $this->sanitiseOtherAttribute($value['values']);
                }
            } else {
                if (isset($key)) {
                    $label = $this->sanitiseOtherAttribute($key);
                }
                if (isset($value)) {
                    $value = $this->sanitiseOtherAttribute($value);
                }
            }

            if (!empty($value)) {
                if (is_array($value)) {
                    $filteredValue = array_filter($value, static function ($item) {
                        return !is_array($item) && !is_object($item);
                    });
                    if (count($filteredValue) !== count($value)) {
                        $this->_searchHelperData->log(
                            LoggerConstants::ZEND_LOG_ERR,
                            __(
                                '%1: Multi dimensional array provided for "other" for SKU %2 : attribute %3.',
                                __METHOD__,
                                $record['sku'],
                                $key
                            )
                        );
                        $value = '';
                    } else {
                        $value = implode(",", $value);
                    }
                }
            }

            if (!empty($value)) {
                if (isset($label)) {
                    $value = sprintf("%s:%s:%s", $key, $label, $value);
                } else {
                    $value = sprintf("%s:%s:%s", $key, $key, $value);
                }
            }
        }
        if (is_array($record['other'])) {
            $other = array_filter($record['other'], static function ($item) {
                return !is_array($item) && !is_object($item);
            });
            if (count($other) !== count($record['other'])) {
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_ERR,
                    __(
                        '%1: Multi dimensional array provided for "other" for SKU %2.',
                        __METHOD__,
                        $record['sku']
                    )
                );
                $record['other'] = '';
            } else {
                $record['other'] = implode(";", $other);
            }
        } elseif (is_scalar($record['other'])) {
            $record['other'] = (string)$record['other'];
        } else {
            $record['other'] = '';
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                __(
                    'Unexpected value provided for "other". %1',
                    is_object($record['other'])
                        ? get_class($record['other'])
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                        : gettype($record['other'])
                )
            );
        }
    }

    /**
     * Flattens otherAttributeToIndex parameters array to a string formatted: key:value[,value]
     *
     * @param array $record
     */
    protected function prepareOtherAttributeToIndexParameters(&$record)
    {
        foreach ($record['otherAttributeToIndex'] as $key => &$value) {
            if ($key === 'created_at') {
                $value = date('Y-m-d', strtotime($value));
            }
            $key = $this->sanitiseOtherAttribute($key);

            if (is_array($value)) {
                if (isset($value['label'])) {
                    $label = $this->sanitiseOtherAttribute($value['label']);
                }
                if (isset($value['values'])) {
                    $value = $this->sanitiseOtherAttribute($value['values']);
                }
            } else {
                if ($key) {
                    $label = $this->sanitiseOtherAttribute($key);
                }
                if ($value) {
                    $value = $this->sanitiseOtherAttribute($value);
                }
            }

            if (!empty($value)) {
                if (is_array($value)) {
                    $filteredValue = array_filter($value, static function ($item) {
                        return !is_array($item) && !is_object($item);
                    });
                    if (count($filteredValue) !== count($value)) {
                        $this->_searchHelperData->log(
                            LoggerConstants::ZEND_LOG_ERR,
                            __(
                                '%1: Multi dimensional array provided for "otherAttributeToIndex" for SKU %2:'
                                . ' attribute %3.',
                                __METHOD__,
                                $record['sku'],
                                $key
                            )
                        );
                        $value = '';
                    } else {
                        $value = implode(",", $value);
                    }
                }
            }
            if (!empty($value) && !empty($label)) {
                $value = sprintf("%s:%s:%s", $key, $label, $value);
            }
        }
        $recordOtherAttributeToIndex = $record['otherAttributeToIndex'];
        if (count($recordOtherAttributeToIndex) > 0) {
            foreach ($recordOtherAttributeToIndex as $key => $element) {
                if (is_array($element) || is_object($element)) {
                    unset($recordOtherAttributeToIndex[$key]);
                }
            }
        }
        if (is_array($recordOtherAttributeToIndex)) {
            $record['otherAttributeToIndex'] = implode(";", $recordOtherAttributeToIndex);
        } elseif (is_scalar($recordOtherAttributeToIndex)) {
            $record['otherAttributeToIndex'] = (string)$recordOtherAttributeToIndex;
        } else {
            $record['otherAttributeToIndex'] = '';
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                __(
                    'Unexpected value provided for "otherAttributeToIndex". %1',
                    is_object($record['otherAttributeToIndex'])
                        ? get_class($record['otherAttributeToIndex'])
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                        : gettype($record['otherAttributeToIndex'])
                )
            );
        }
    }

    /**
     * Flattens GroupPrices parameters array to a string formatted: key:value[,value]
     *
     * @param array $record
     */
    protected function prepareGroupPricesParameters(&$record)
    {
        foreach ($record['groupPrices'] as $key => &$value) {
            $key = $this->sanitiseOtherAttribute($key);

            if (is_array($value)) {
                $label = $this->sanitiseOtherAttribute($value['label']);
                $value = $this->sanitiseOtherAttribute($value['values']);
            } else {
                $label = $this->sanitiseOtherAttribute($key);
                $value = $this->sanitiseOtherAttribute($value);
            }

            if (is_array($value)) {
                $value = implode(",", $value);
            }

            $value = sprintf("%s:%s:%s", $key, $label, $value);
        }
        $record['groupPrices'] = implode(";", $record['groupPrices']);
    }

    /**
     * Remove the characters used to organise the other attribute values from the
     * passed in string.
     *
     * @param string $value
     *
     * @return string
     */
    protected function sanitiseOtherAttribute($value)
    {
        return $this->_searchHelperData->santiseAttributeValue($value);
    }

    /**
     * @param string $endpoint
     * @param string $store
     * @param string $hostname
     *
     * @return string
     */
    public function buildEndpoint($endpoint, $store = null, $hostname = null)
    {
        return static::ENDPOINT_PROTOCOL .
            (($hostname) ? $hostname : $this->_searchHelperConfig->getHostname($store)) .
            $endpoint;
    }
}
