<?php

namespace Klevu\Search\Test\Integration\Provider\Customer;

use Klevu\Search\Api\Provider\Customer\CustomerIdProviderInterface;
use Klevu\Search\Provider\Customer\CustomerIdProvider;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class CustomerIdProviderTest extends TestCase
{
    const HASH_SHA_256 = 'sha256';

    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var array|false|string[]
     */
    private $keys;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();
        $customerIdProvider = $this->instantiateCustomerIdProvider();
        $this->assertInstanceOf(CustomerIdProvider::class, $customerIdProvider);
    }

    public function testSessionIdProviderEncryptsSessionIdWithSha256()
    {
        $this->setupPhp5();
        $email = 'someone@klevu.com';

        $customerIdProvider = $this->instantiateCustomerIdProvider();
        $actualHash = $customerIdProvider->execute($email);

        $expectedHash = CustomerIdProvider::CUSTOMER_EMAIL_PREFIX . '-' .
            hash_hmac(self::HASH_SHA_256, $email, $this->keys[count($this->keys) - 1]);

        $this->assertSame($expectedHash, $actualHash);
    }

    /**
     * @return CustomerIdProviderInterface
     */
    protected function instantiateCustomerIdProvider()
    {
        $encryptor = $this->objectManager->get(EncryptorInterface::class);

        return $this->objectManager->create(CustomerIdProviderInterface::class, [
            'encryptor' => $encryptor
        ]);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();

        $deploymentConfig = $this->objectManager->get(DeploymentConfig::class);
        $this->keys = preg_split('/\s+/s', trim((string)$deploymentConfig->get(Encryptor::PARAM_CRYPT_KEY)));
    }
}
