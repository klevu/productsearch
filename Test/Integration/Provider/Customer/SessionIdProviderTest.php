<?php

namespace Klevu\Search\Test\Integration\Provider\Customer;

use Klevu\Search\Api\Provider\Customer\SessionIdProviderInterface;
use Klevu\Search\Provider\Customer\SessionIdProvider;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionIdProviderTest extends TestCase
{
    const HASH_SHA_256 = 'sha256';
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var SessionManagerInterface|MockObject
     */
    private $mockSessionManager;
    /**
     * @var array|false|string[]
     */
    private $keys;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();
        $sessionIdProvider = $this->instantiateSessionIdProvider();
        $this->assertInstanceOf(SessionIdProvider::class, $sessionIdProvider);
    }

    public function testSessionIdProviderEncryptsSessionIdWithSha256()
    {
        $this->setupPhp5();
        $sessionId = '1234567890';

        $this->mockSessionManager->expects($this->once())
            ->method('getSessionId')
            ->willReturn($sessionId);

        $sessionIdProvider = $this->instantiateSessionIdProvider();
        $actualHash = $sessionIdProvider->execute();

        $expectedHash = hash_hmac(self::HASH_SHA_256, $sessionId, $this->keys[count($this->keys) - 1]);

        $this->assertSame($expectedHash, $actualHash);
    }

    /**
     * @return SessionIdProviderInterface
     */
    protected function instantiateSessionIdProvider()
    {
        $encryptor = $this->objectManager->get(EncryptorInterface::class);

        return $this->objectManager->create(SessionIdProviderInterface::class, [
            'encryptor' => $encryptor,
            'sessionManager' => $this->mockSessionManager
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

        $this->mockSessionManager = $this->getMockBuilder(SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $deploymentConfig = $this->objectManager->get(DeploymentConfig::class);
        $this->keys = preg_split('/\s+/s', trim((string)$deploymentConfig->get(Encryptor::PARAM_CRYPT_KEY)));
    }
}
