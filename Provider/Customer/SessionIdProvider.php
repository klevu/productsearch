<?php

namespace Klevu\Search\Provider\Customer;

use Klevu\Search\Api\Provider\Customer\SessionIdProviderInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Session\SessionManagerInterface;

class SessionIdProvider implements SessionIdProviderInterface
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    public function __construct(
        EncryptorInterface $encryptor,
        SessionManagerInterface $sessionManager
    ) {
        $this->encryptor = $encryptor;
        $this->sessionManager = $sessionManager;
    }

    /**
     * @return string
     */
    public function execute()
    {
        return $this->encryptor->hash(
            $this->sessionManager->getSessionId(),
            Encryptor::HASH_VERSION_SHA256
        );
    }
}
