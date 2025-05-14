<?php

namespace Klevu\Search\Provider\Customer;

use Klevu\Search\Api\Provider\Customer\SessionIdProviderInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Session\SessionManagerInterface;

class SessionIdProvider implements SessionIdProviderInterface
{
    use HashTrait;

    /**
     * @var EncryptorInterface
     * @deprecated changes to the core Magento hash method mean we can not continue to use it for this purpose.
     * @see HashTrait
     */
    private $encryptor;
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @param EncryptorInterface $encryptor
     * @param SessionManagerInterface $sessionManager
     * @param DeploymentConfig|null $deploymentConfig
     *
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function __construct(
        EncryptorInterface $encryptor,
        SessionManagerInterface $sessionManager,
        ?DeploymentConfig $deploymentConfig = null
    ) {
        $this->encryptor = $encryptor;
        $this->sessionManager = $sessionManager;
        $deploymentConfig = $deploymentConfig ?: ObjectManager::getInstance()->get(DeploymentConfig::class);
        $this->keys = preg_split(
            '/\s+/s',
            trim((string)$deploymentConfig->get(Encryptor::PARAM_CRYPT_KEY))
        );
        $this->keyVersion = count($this->keys) - 1;
    }

    /**
     * @return string
     */
    public function execute()
    {
        return $this->hash(
            $this->sessionManager->getSessionId(),
            Encryptor::HASH_VERSION_SHA256
        );
    }
}
