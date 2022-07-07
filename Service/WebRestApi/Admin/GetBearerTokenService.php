<?php

namespace Klevu\Search\Service\WebRestApi\Admin;

use Klevu\Search\Api\Service\WebRestApi\Admin\GetBearerTokenInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Integration\Model\Oauth\TokenFactory;

class GetBearerTokenService implements GetBearerTokenInterface
{
    /**
     * @var TokenFactory
     */
    private $tokenFactory;
    /**
     * @var AdminSession
     */
    private $adminSession;

    public function __construct(
        TokenFactory $tokenFactory,
        AdminSession $adminSession
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->adminSession = $adminSession;
    }

    /**
     * @return string
     * Is required due to an issue in the core of Magento.
     * Admin Session does not auth admin ajax api calls as it should.
     * https://github.com/magento/magento2/issues/14297
     */
    public function execute()
    {
        $adminUser = $this->adminSession->getUser();
        if (!$adminUser || !$adminUser->getId()) {
            return '';
        }

        $token = $this->tokenFactory->create();
        $token->createAdminToken($adminUser->getId());

        return $token->getToken();
    }
}