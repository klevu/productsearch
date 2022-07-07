<?php

namespace Klevu\Search\Test\Integration\Controller\Adminhtml\System\Config\Edit;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\Request\Http\Proxy as RequestProxy;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;

class IntegrationAclTest extends AbstractBackendControllerTestCase
{
    /**
     * @var string
     */
    protected $uri = 'admin/system_config/edit/section/klevu_integration';

    /**
     * @var string
     */
    protected $resource = 'Klevu_Search::integration';

    /**
     * Expected no access response
     *
     * @var int
     */
    protected $expectedNoAccessResponseCode = 302;

    /**
     * {@inheritdoc}
     */
    public function testAclHasAccess()
    {
        $this->setupPhp5();

        /** @var RequestProxy $request */
        $request = $this->getRequest();
        $request->setParam('section', 'klevu_integration');
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->assertNotSame(404, $this->getResponse()->getHttpResponseCode());
        $this->assertNotSame($this->expectedNoAccessResponseCode, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * {@inheritdoc}
     */
    public function testAclNoAccess()
    {
        $this->setupPhp5();

        /** @var RequestProxy $request */
        $request = $this->getRequest();
        $request->setParam('section', 'klevu_integration');
        $request->setMethod('GET');

        $aclBuilder = $this->objectManager->get(AclBuilder::class);
        $acl = $aclBuilder->getAcl();
        $acl->deny(null, $this->resource);

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/edit');
        $this->assertSame($this->expectedNoAccessResponseCode, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * Returns configured admin front name for use in dispatching controller requests
     *
     * @return string
     */
    private function getAdminFrontName()
    {
        /** @var AreaList $areaList */
        $areaList = $this->objectManager->get(AreaList::class);
        $adminFrontName = $areaList->getFrontName('adminhtml');
        if (!$adminFrontName) {
            /** @var FrontNameResolver $backendFrontNameResolver */
            $backendFrontNameResolver = $this->objectManager->get(FrontNameResolver::class);
            $adminFrontName = $backendFrontNameResolver->getFrontName(true);
        }

        return (string)$adminFrontName;
    }

    /**
     * Alternative setup method to accommodate lack of return type casting in PHP5.6,
     *  given setUp() requires a void return type
     *
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        if (!isset($this->expectedNoAccessResponseCode)) {
            $this->expectedNoAccessResponseCode = 403;
        }

        $this->objectManager = Bootstrap::getObjectManager();
        $this->resourceConnection = $this->objectManager->get(ResourceConnection::class);
        $this->connection = $this->resourceConnection->getConnection();
    }
}
