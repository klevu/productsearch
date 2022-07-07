<?php

namespace Klevu\Search\Test\Integration\Service\WebRestApi\Admin;

use Klevu\Search\Api\Service\WebRestApi\Admin\GetBearerTokenInterface;
use Klevu\Search\Service\WebRestApi\Admin\GetBearerTokenService;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\TestFramework\ObjectManager;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

class GetBearerTokenTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testIsInstanceOfGetBearerTokenInterface()
    {
        $this->setupPhp5();
        $getBearerToken = $this->objectManager->get(GetBearerTokenInterface::class);

        $this->assertInstanceOf(GetBearerTokenService::class, $getBearerToken);
    }

    public function testEmptyStringIsReturnedIfNoAdminUser()
    {
        $this->setupPhp5();

        $getBearerToken = $this->objectManager->get(GetBearerTokenInterface::class);
        $bearerToken = $getBearerToken->execute();

        $this->assertSame('', $bearerToken);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppArea adminhtml
     * @magentoDataFixture loadUserFixtures
     */
    public function testTokenIsReturnedForAdminUser()
    {
        $this->setupPhp5();

        $user = $this->getUser();

        $mockAdminSessionBuilder = $this->getMockBuilder(AdminSession::class);
        if (method_exists($mockAdminSessionBuilder, 'addMethods')) {
            $mockAdminSessionBuilder->addMethods(['getUser']);
        } else {
            $mockAdminSessionBuilder->setMethods(['getUser']);
        }
        $mockAdminSession = $mockAdminSessionBuilder->disableOriginalConstructor()
            ->getMock();
        $mockAdminSession->expects($this->once())->method('getUser')->willReturn($user);

        $getBearerToken = $this->objectManager->create(GetBearerTokenInterface::class, [
            'adminSession' => $mockAdminSession
        ]);

        $bearerToken = $getBearerToken->execute();

        $this->assertNotSame('', $bearerToken);

        static::loadUserFixturesRollback();
    }

    /**
     * @param $userName
     *
     * @return User
     */
    private function getUser($userName = 'dummy_username')
    {
        $user = $this->objectManager->get(User::class);

        return $user->loadByUsername($userName);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Loads user creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadUserFixtures()
    {
        include __DIR__ . '/../../../_files/adminUserFixtures.php';
    }

    /**
     * Rolls back user creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadUserFixturesRollback()
    {
        include __DIR__ . '/../../../_files/adminUserFixtures_rollback.php';
    }
}
