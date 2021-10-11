<?php

namespace Klevu\Search\Api;

/**
 * This interface exists to backport Magento\Framework\Serialize\SerializerInterface into Magento < 2.2
 * @todo Remove Klevu\Search\Api\SerializerInterface when support for Magento < 2.2 is dropped
 * @link https://devdocs.magento.com/guides/v2.4/extension-dev-guide/framework/serializer.html#backward-compatibility-note
 */
interface SerializerInterface
{
    /**
     * Serialize data into string
     *
     * @param string|int|float|bool|array|null $data
     * @return string|bool
     * @throws \InvalidArgumentException
     */
    public function serialize($data);

    /**
     * Unserialize the given string
     *
     * @param string $string
     * @return string|int|float|bool|array|null
     */
    public function unserialize($string);
}