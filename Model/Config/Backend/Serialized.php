<?php

namespace Klevu\Search\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;

class Serialized extends ConfigValue
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        $data = [],
        SerializerInterface $serializer = null
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
    }

    /**
     * @return void
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            return;
        }
        if (!empty($value)) {
            $value = $this->isJson($value) ?
                json_decode($value, true) :
                $this->serializer->unserialize($value);
        }
        $this->setValue(empty($value) ? false : $value);
    }

    /**
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            unset($value['__empty']);
            $this->setValue($this->serializer->serialize($value));
        }

        return parent::beforeSave();
    }

    /**
     * Checks if the given value is json encoded
     *
     * @param mixed $sValue
     *
     * @return bool
     */
    public function isJson($sValue)
    {
        return is_string($sValue) &&
            is_array(json_decode($sValue, true)) &&
            (json_last_error() === JSON_ERROR_NONE);
    }
}
