<?php

namespace Klevu\Search\Test\Integration\Controller\Adminhtml\System\Config\Save;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\Request\Http\Proxy as RequestProxy;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;

/**
 * Tests actions taken as a result of saving changes in Stores > Configuration
 */
class UpdateTriggerOptionsTest extends AbstractBackendControllerTestCase
{
    /**
     * {@inheritdoc}
     * @var string
     */
    protected $uri = 'admin/system_config/save/section/klevu_search';

    /**
     * {@inheritdoc}
     * @var string
     */
    protected $resource = 'Klevu_Search::config_search';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * Feature: Database triggers can be enabled / disabled through stores config
     *
     * Scenario: Admin users can disable database triggers by changing a config value and submitting the page
     *    Given: "Using an ERP OR 3rd party tool for product updates" is set to Yes
     *      and: Database triggers do not already exist
     *     When: The "Using an ERP OR 3rd party tool for product updates" is set to No
     *      and: The stores configuration admin form is submitted
     *     Then: Database triggers are not created
     *
     * @magentoConfigFixture default/klevu_search/developer/trigger_options_info 1
     * @throws \Zend_Db_Statement_Exception
     */
    public function testDisableTriggersWhenNotEnabledConfigChanged()
    {
        $this->setupPhp5();

        $fixtures = [
            'update_klevuproductsync_for_cpip',
            'update_klevuproductsync_for_lsa',
            'update_klevuproductsync_for_cpp',
        ];
        $this->removeTriggers($fixtures);

        /** @var RequestProxy $request */
        $request = $this->getRequest();
        $request->setParam('section', 'klevu_search');
        $request->setMethod('POST');
        $request->setPostValue([
            'groups' => [
                'developer' => [
                    'fields' => [
                        'trigger_options_info' => [
                            'value' => 0,
                        ],
                    ],
                ],
            ],
        ]);

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/save');
        $response = $this->getResponse();

        $this->assertTrue($response->isRedirect(), 'Response is redirect');

        $existingTriggerNames = $this->getExistingTriggerNames();
        foreach ($fixtures as $fixtureTriggerName) {
            $this->assertFalse(
                in_array($fixtureTriggerName, $existingTriggerNames, true),
                sprintf('Assert fixture "%s" does not exist', $fixtureTriggerName)
            );
        }
    }

    /**
     * Feature: Database triggers can be enabled / disabled through stores config
     *
     * Scenario: Admin users can disable database triggers by changing a config value and submitting the page
     *    Given: "Using an ERP OR 3rd party tool for product updates" is set to No
     *      and: Database triggers do not already exist
     *     When: The "Using an ERP OR 3rd party tool for product updates" is set to No (ie unchanged)
     *      and: The stores configuration admin form is submitted
     *     Then: Database triggers are not created
     *
     * @magentoConfigFixture default/klevu_search/developer/trigger_options_info 0
     * @throws \Zend_Db_Statement_Exception
     */
    public function testDisableTriggersWhenNotEnabledConfigNotChanged()
    {
        $this->setupPhp5();

        $fixtures = [
            'update_klevuproductsync_for_cpip',
            'update_klevuproductsync_for_lsa',
            'update_klevuproductsync_for_cpp',
        ];
        $this->removeTriggers($fixtures);

        /** @var RequestProxy $request */
        $request = $this->getRequest();
        $request->setParam('section', 'klevu_search');
        $request->setMethod('POST');
        $request->setPostValue([
            'groups' => [
                'developer' => [
                    'fields' => [
                        'trigger_options_info' => [
                            'value' => 0,
                        ],
                    ],
                ],
            ],
        ]);

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/save');
        $response = $this->getResponse();

        $this->assertTrue($response->isRedirect(), 'Response is redirect');

        $existingTriggerNames = $this->getExistingTriggerNames();
        foreach ($fixtures as $fixtureTriggerName) {
            $this->assertFalse(
                in_array($fixtureTriggerName, $existingTriggerNames, true),
                sprintf('Assert fixture "%s" does not exist', $fixtureTriggerName)
            );
        }
    }

    /**
     * Feature: Database triggers can be enabled / disabled through stores config
     *
     * Scenario: Admin users can disable database triggers by changing a config value and submitting the page
     *    Given: "Using an ERP OR 3rd party tool for product updates" is set to Yes
     *      and: Database triggers already exist
     *     When: The "Using an ERP OR 3rd party tool for product updates" is set to No
     *      and: The stores configuration admin form is submitted
     *     Then: Database triggers are removed
     *
     * @magentoConfigFixture default/klevu_search/developer/trigger_options_info 1
     * @throws \Zend_Db_Statement_Exception
     */
    public function testDisableTriggersWhenEnabledConfigChanged()
    {
        $this->setupPhp5();

        $fixtures = [
            'update_klevuproductsync_for_cpip',
            'update_klevuproductsync_for_lsa',
            'update_klevuproductsync_for_cpp',
        ];
        $this->createTriggers($fixtures);

        /** @var RequestProxy $request */
        $request = $this->getRequest();
        $request->setParam('section', 'klevu_search');
        $request->setMethod('POST');
        $request->setPostValue([
            'groups' => [
                'developer' => [
                    'fields' => [
                        'trigger_options_info' => [
                            'value' => 0,
                        ],
                    ],
                ],
            ],
        ]);

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/save');
        $response = $this->getResponse();

        $this->assertTrue($response->isRedirect(), 'Response is redirect');

        $existingTriggerNames = $this->getExistingTriggerNames();
        foreach ($fixtures as $fixtureTriggerName) {
            $this->assertFalse(
                in_array($fixtureTriggerName, $existingTriggerNames, true),
                sprintf('Assert fixture "%s" does not exist', $fixtureTriggerName)
            );
        }
    }

    /**
     * Feature: Database triggers can be enabled / disabled through stores config
     *
     * Scenario: Admin users can disable database triggers by changing a config value and submitting the page
     *    Given: "Using an ERP OR 3rd party tool for product updates" is set to No
     *      and: Database triggers already exist
     *     When: The "Using an ERP OR 3rd party tool for product updates" is set to No (ie unchanged)
     *      and: The stores configuration admin form is submitted
     *     Then: Database triggers are not removed
     *
     * @magentoConfigFixture default/klevu_search/developer/trigger_options_info 0
     * @throws \Zend_Db_Statement_Exception
     */
    public function testDisableTriggersWhenEnabledConfigNotChanged()
    {
        $this->setupPhp5();

        $fixtures = [
            'update_klevuproductsync_for_cpip',
            'update_klevuproductsync_for_lsa',
            'update_klevuproductsync_for_cpp',
        ];

        /** @var RequestProxy $request */
        $request = $this->getRequest();
        $request->setParam('section', 'klevu_search');
        $request->setMethod('POST');
        $request->setPostValue([
            'groups' => [
                'developer' => [
                    'fields' => [
                        'trigger_options_info' => [
                            'value' => 0,
                        ],
                    ],
                ],
            ],
        ]);

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/save');
        $response = $this->getResponse();

        $this->assertTrue($response->isRedirect(), 'Response is redirect');

        $existingTriggerNames = $this->getExistingTriggerNames();
        foreach ($fixtures as $fixtureTriggerName) {
            $this->assertFalse(
                in_array($fixtureTriggerName, $existingTriggerNames, true),
                sprintf('Assert trigger "%s" exists', $fixtureTriggerName)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function testAclHasAccess()
    {
        $this->setupPhp5();

        /** @var RequestProxy $request */
        $request = $this->getRequest();
        $request->setParam('section', 'klevu_search');
        $request->setMethod('POST');
        $request->setPostValue([]);

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/save');
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
        $request->setParam('section', 'klevu_search');
        $request->setMethod('POST');
        $request->setPostValue([
            'groups' => [
                'developer' => [
                    'fields' => [
                        'trigger_options_info' => [
                            'value' => 0,
                        ],
                    ],
                ],
            ],
        ]);

        $aclBuilder = $this->objectManager->get(AclBuilder::class);
        $acl = $aclBuilder->getAcl();
        $acl->deny($this->_auth->getUser()->getRoles(), $this->resource);

        $this->dispatch($this->getAdminFrontName() . '/admin/system_config/save');
        $this->assertSame($this->expectedNoAccessResponseCode, $this->getResponse()->getHttpResponseCode());
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
     * Creates dummy triggers with given names via direct SQL
     * Used to set initial test conditions
     *
     * @param string[] $triggerNamesToCreate
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function createTriggers(array $triggerNamesToCreate)
    {
        $existingTriggerNames = $this->getExistingTriggerNames();
        $missingTriggerNames = array_diff($triggerNamesToCreate, $existingTriggerNames);

        foreach ($missingTriggerNames as $missingTriggerName) {
            $this->connection->query(sprintf(
                'CREATE TRIGGER %s BEFORE UPDATE ON %s FOR EACH ROW SET NEW.send = NEW.send * 1',
                $missingTriggerName,
                $this->resourceConnection->getTableName('klevu_order_sync')
            ));
        }

        $existingTriggerNames = $this->getExistingTriggerNames();
        foreach ($triggerNamesToCreate as $triggerName) {
            $this->assertTrue(in_array($triggerName, $existingTriggerNames, true));
        }
    }

    /**
     * Removes existing database triggers with given names via direct SQL
     * Used to set initial test conditions
     *
     * @param string[] $triggerNamesToRemove
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function removeTriggers(array $triggerNamesToRemove)
    {
        foreach ($triggerNamesToRemove as $triggerName) {
            $this->connection->query(sprintf('DROP TRIGGER IF EXISTS %s', $triggerName));
        }

        $existingTriggerNames = $this->getExistingTriggerNames();
        foreach ($existingTriggerNames as $existingTriggerName) {
            $this->assertFalse(in_array($existingTriggerName, $triggerNamesToRemove, true));
        }
    }

    /**
     * Returns list of all existing database trigger names in database
     *
     * @return string[]
     * @throws \Zend_Db_Statement_Exception
     */
    private function getExistingTriggerNames()
    {
        $triggersResult = $this->connection->query('SHOW TRIGGERS');

        return array_column($triggersResult->fetchAll(), 'Trigger');
    }
}
