<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Provider\Customer;

use Magento\Framework\Encryption\Encryptor;

trait HashTrait
{
    /**
     * Map of simple hash versions
     *
     * @var array
     */
    private $hashVersionMap = [
        Encryptor::HASH_VERSION_MD5 => 'md5',
        Encryptor::HASH_VERSION_SHA256 => 'sha256',
    ];
    /**
     * Version of encryption key
     *
     * @var int
     */
    private $keyVersion;

    /**
     * Array of encryption keys
     *
     * @var string[]
     */
    private $keys = [];

    /**
     * Hash a string.
     * Returns one-way encrypted string, always the same result for the same value. Suitable for signatures.
     *
     * @param string $data
     * @param int $version
     *
     * @return string
     *
     * Magento updated this method in 2.4.7 and decoded the key.
     * This lead to our customer and session identifiers changing for the same data.
     * @see Encryptor::hash
     */
    public function hash($data, $version = Encryptor::HASH_VERSION_SHA256)
    {
        if (empty($this->keys[$this->keyVersion])) {
            throw new \RuntimeException('No key available');
        }
        if (!array_key_exists($version, $this->hashVersionMap)) {
            throw new \InvalidArgumentException('Unknown hashing algorithm');
        }

        return hash_hmac(
            $this->hashVersionMap[$version],
            (string)$data,
            $this->keys[$this->keyVersion],
            false
        );
    }
}
