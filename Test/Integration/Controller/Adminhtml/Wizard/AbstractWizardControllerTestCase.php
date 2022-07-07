<?php

namespace Klevu\Search\Test\Integration\Controller\Adminhtml\Wizard;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\AreaList;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;

abstract class AbstractWizardControllerTestCase extends AbstractBackendControllerTestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    public function testAclHasAccess()
    {
        $this->markTestSkipped('There is no ACL set for this route');
    }

    public function testAclNoAccess()
    {
        $this->markTestSkipped('There is no ACL set for this route');
    }

    /**
     * Returns configured admin front name for use in dispatching controller requests
     *
     * @return string
     */
    protected function getAdminFrontName()
    {
        $this->setupPhp5();

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

    /***
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    protected function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }
}
