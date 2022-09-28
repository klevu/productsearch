<?php

namespace Klevu\Search\Provider\Customer;

use Klevu\Search\Api\Provider\Customer\CustomerIdProviderInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;

class CustomerIdProvider implements CustomerIdProviderInterface
{
    const CUSTOMER_EMAIL_PREFIX = 'cep';

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    public function __construct(
        EncryptorInterface $encryptor
    ) {
        $this->encryptor = $encryptor;
    }

    /**
     * @param string $email
     *
     * @return string
     */
    public function execute($email)
    {
        return sprintf(
            '%s-%s',
            self::CUSTOMER_EMAIL_PREFIX,
            $this->encryptor->hash($email, Encryptor::HASH_VERSION_SHA256)
        );
    }
}
