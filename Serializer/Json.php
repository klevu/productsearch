<?php

namespace Klevu\Search\Serializer;

use Klevu\Search\Api\SerializerInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Polyfill of JSON serializer class for dependency injection providing support for Magento < 2.2.x
 * @todo Remove Klevu\Search\Serializer\Json when support for Magento < 2.2 is dropped
 * @link https://devdocs.magento.com/guides/v2.4/extension-dev-guide/framework/serializer.html#backward-compatibility-note
 */
class Json implements SerializerInterface
{
    /**
     * {@inheritdoc}
     *
     * @param array|bool|float|int|string|null $data
     * @return bool|string
     * @throws \InvalidArgumentException
     */
    public function serialize($data)
    {
        if (class_exists(\Magento\Framework\Serialize\Serializer\Json::class)) {
            /** @var \Magento\Framework\Serialize\Serializer\Json $serializer */
            $serializer = ObjectManager::getInstance()->get(\Magento\Framework\Serialize\Serializer\Json::class);

            return $serializer->serialize($data);
        }

        $result = json_encode($data);
        if (false === $result) {
            throw new \InvalidArgumentException("Unable to serialize value. Error: " . json_last_error_msg());
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $string
     * @return array|bool|float|int|mixed|string|null
     * @throws \InvalidArgumentException
     */
    public function unserialize($string)
    {
        if (class_exists(\Magento\Framework\Serialize\Serializer\Json::class)) {
            /** @var \Magento\Framework\Serialize\Serializer\Json $serializer */
            $serializer = ObjectManager::getInstance()->get(\Magento\Framework\Serialize\Serializer\Json::class);

            return $serializer->unserialize($string);
        }

        $result = json_decode($string, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("Unable to unserialize value. Error: " . json_last_error_msg());
        }

        return $result;
    }
}
