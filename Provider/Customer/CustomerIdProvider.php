<?php

namespace Klevu\Search\Provider\Customer;

use Klevu\Search\Api\Provider\Customer\CustomerIdProviderInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;

class CustomerIdProvider implements CustomerIdProviderInterface
{
    use HashTrait;

    const CUSTOMER_EMAIL_PREFIX = 'cep';

    /**
     * @var EncryptorInterface
     * @deprecated changes to the core Magento hash method mean we can not continue to use it for this purpose.
     * @see HashTrait
     */
    private $encryptor;

    /**
     * @param EncryptorInterface $encryptor
     * @param DeploymentConfig $deploymentConfig
     *
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function __construct(
        EncryptorInterface $encryptor,
        DeploymentConfig $deploymentConfig = null
    ) {
        $this->encryptor = $encryptor;
        $deploymentConfig = $deploymentConfig ?: ObjectManager::getInstance()->get(DeploymentConfig::class);
        $this->keys = preg_split(
            '/\s+/s',
            trim((string)$deploymentConfig->get(Encryptor::PARAM_CRYPT_KEY))
        );
        $this->keyVersion = count($this->keys) - 1;
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
            $this->hash($email, Encryptor::HASH_VERSION_SHA256)
        );
    }
}
