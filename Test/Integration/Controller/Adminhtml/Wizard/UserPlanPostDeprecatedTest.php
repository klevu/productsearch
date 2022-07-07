<?php

namespace Klevu\Search\Test\Integration\Controller\Adminhtml\Wizard;

class UserPlanPostDeprecatedTest extends AbstractWizardControllerTestCase
{
    /**
     * @var string
     */
    protected $uri = 'admin/klevu_search/wizard/userplan/post';
    /**
     * null resource will cause a skipped test, unfortunately there is no acl set for this route.
     * @var null
     */
    protected $resource = null;

    public function testRouteReturns404()
    {
        $this->setupPhp5();

        $this->dispatch($this->getAdminFrontName() . '/' . $this->uri);
        $response = $this->getResponse();

        $this->assertSame(404, $response->getHttpResponseCode());
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('404 Error', $response->getBody());
        } else {
            $this->assertContains('404 Error', $response->getBody());
        }
    }
}
