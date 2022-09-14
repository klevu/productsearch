<?php

namespace Klevu\Search\Test\Unit\Service\Account;

use Klevu\Search\Service\Account\AccountFeaturesMask;
use Klevu\Search\Service\Account\GetFeatures as GetFeaturesService;
use Klevu\Search\Service\Account\Model\AccountFeatures as AccountFeaturesModel;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AccountFeaturesMaskTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testIsFeatureEnabled_V1FlagNotFound_V2FlagNotFound()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront',
        ];
        $v2Fixture = [
            [
                'key' => 's.enablecategorynavigation',
                'value' => 'yes',
            ], [
                'key' => 'allow.personalizedrecommendations',
                'value' => 'yes',
            ], [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $this->assertNull(
            $accountFeaturesMask->isFeatureEnabled('foo', $v1Fixture, $v2Fixture)
        );
    }

    public function testIsFeatureEnabled_V1FlagNotFound_V2FlagFoundEnabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront',
        ];
        $v2Fixture = [
            [
                'key' => 's.enablecategorynavigation',
                'value' => 'yes',
            ], [
                'key' => 'allow.personalizedrecommendations',
                'value' => 'yes',
            ], [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $this->assertTrue(
            $accountFeaturesMask->isFeatureEnabled(AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT, $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertTrue(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagNotFound_V2FlagFoundDisabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront',
        ];
        $v2Fixture = [
            [
                'key' => 's.enablecategorynavigation',
                'value' => 'yes',
            ], [
                'key' => 'allow.personalizedrecommendations',
                'value' => 'no',
            ], [
                'key' => 's.preservedlayout',
                'value' => 'no',
            ],
        ];

        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled(AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT, $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    /**
     * @depends testIsFeatureEnabled_V1FlagNotFound_V2FlagFoundEnabled
     */
    public function testIsFeatureEnabled_V1FlagNotFound_V2FlagFoundEnabled_WithoutMapping()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront',
        ];
        $v2Fixture = [
            [
                'key' => 's.enablecategorynavigation',
                'value' => 'yes',
            ], [
                'key' => 'allow.personalizedrecommendations',
                'value' => 'yes',
            ], [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $this->assertNull(
            $accountFeaturesMask->isFeatureEnabled(AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT, $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertNull(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagFoundEnabled_V2FlagNotFound()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,preserves_layout',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront',
        ];
        $v2Fixture = [];

        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled(AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT, $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagFoundEnabled_V2FlagNotFound_WithoutMapping()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,preserves_layout',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront',
        ];
        $v2Fixture = [];

        $this->assertTrue(
            $accountFeaturesMask->isFeatureEnabled(AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT, $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertNull(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagFoundDisabled_V2FlagNotFound()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,preserves_layout',
        ];
        $v2Fixture = [];

        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled(AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT, $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagFoundDisabled_V2FlagNotFound_WithoutMapping()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,preserves_layout',
        ];
        $v2Fixture = [];

        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled(AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT, $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertNull(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagFoundEnabledUnknown_V2FlagNotFound()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => 'foo',
                    'v2Flag' => 'bar',
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront',
        ];
        $v2Fixture = [];

        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled('foo', $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled('bar', $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagFoundEnabledUnknown_V2FlagNotFound_WithoutMapping()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront',
        ];
        $v2Fixture = [];

        $this->assertTrue(
            $accountFeaturesMask->isFeatureEnabled('foo', $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertNull(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagFoundDisabledUnknown_V2FlagNotFound()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => 'foo',
                    'v2Flag' => 'bar',
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,foo',
        ];
        $v2Fixture = [];

        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled('foo', $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled('bar', $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagFoundDisabledUnknown_V2FlagNotFound_WithoutMapping()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,foo',
        ];
        $v2Fixture = [];

        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled('foo', $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertNull(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagFoundDisabled_V2FlagFoundDisabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,preserves_layout',
        ];
        $v2Fixture = [
            [
                'key' => 's.enablecategorynavigation',
                'value' => 'yes',
            ], [
                'key' => 'allow.personalizedrecommendations',
                'value' => 'no',
            ], [
                'key' => 's.preservedlayout',
                'value' => 'no',
            ],
        ];

        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled(AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT, $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagFoundDisabled_V2FlagFoundEnabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,preserves_layout',
        ];
        $v2Fixture = [
            [
                'key' => 's.enablecategorynavigation',
                'value' => 'yes',
            ], [
                'key' => 'allow.personalizedrecommendations',
                'value' => 'no',
            ], [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $this->assertTrue(
            $accountFeaturesMask->isFeatureEnabled(AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT, $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertTrue(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagFoundEnabled_V2FlagFoundDisabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,preserves_layout',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront',
        ];
        $v2Fixture = [
            [
                'key' => 's.enablecategorynavigation',
                'value' => 'yes',
            ], [
                'key' => 'allow.personalizedrecommendations',
                'value' => 'no',
            ], [
                'key' => 's.preservedlayout',
                'value' => 'no',
            ],
        ];

        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled(AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT, $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertFalse(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_V1FlagFoundEnabled_V2FlagFoundEnabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,preserves_layout',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront',
        ];
        $v2Fixture = [
            [
                'key' => 's.enablecategorynavigation',
                'value' => 'yes',
            ], [
                'key' => 'allow.personalizedrecommendations',
                'value' => 'no',
            ], [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $this->assertTrue(
            $accountFeaturesMask->isFeatureEnabled(AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT, $v1Fixture, $v2Fixture),
            'V1 Feature String'
        );
        $this->assertTrue(
            $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
            'V2 Feature String'
        );
    }

    public function testIsFeatureEnabled_WhitelistedV2FlagValues()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,preserves_layout',
        ];
        $v2Fixtures = [
            [
                'fixture' => [
                    [
                        'key' => 's.preservedlayout',
                        'value' => 'yes',
                    ],
                ],
                'expectedResult' => true,
            ], [
                'fixture' => [
                    [
                        'key' => 's.preservedlayout',
                        'value' => ' YES ',
                    ],
                ],
                'expectedResult' => true,
            ], [
                'fixture' => [
                    [
                        'key' => 's.preservedlayout',
                        'value' => 'enabled',
                    ],
                ],
                'expectedResult' => false,
            ], [
                'fixture' => [
                    [
                        'key' => 's.preservedlayout',
                        'value' => 'no',
                    ],
                ],
                'expectedResult' => false,
            ], [
                'fixture' => [
                    [
                        'key' => 's.preservedlayout',
                        'value' => 'disabled',
                    ],
                ],
                'expectedResult' => false,
            ], [
                'fixture' => [
                    [
                        'key' => 's.preservedlayout',
                        'value' => ' ',
                    ],
                ],
                'expectedResult' => false,
            ], [
                'fixture' => [
                    [
                        'key' => 's.preservedlayout',
                        'value' => true,
                    ],
                ],
                'expectedResult' => false,
            ], [
                'fixture' => [
                    [
                        'key' => 's.preservedlayout',
                        'value' => ['yes'],
                    ],
                ],
                'expectedResult' => false,
            ], [
                'fixture' => [
                    [
                        'key' => 's.preservedlayout',
                        'value' => (object)['yes'],
                    ],
                ],
                'expectedResult' => false,
            ], [
                'fixture' => [
                    [
                        'key' => 's.preservedlayout',
                        'value' => null,
                    ],
                ],
                'expectedResult' => false,
            ], [
                'fixture' => [
                    [
                        'key' => 's.preservedlayout',
                        'value' => 1,
                    ],
                ],
                'expectedResult' => false,
            ],
        ];

        foreach ($v2Fixtures as $i => $v2FixtureRow) {
            $v2Fixture = $v2FixtureRow['fixture'];
            $expectedResult = $v2FixtureRow['expectedResult'];

            $this->assertSame(
                $expectedResult,
                $accountFeaturesMask->isFeatureEnabled(AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT, $v1Fixture, $v2Fixture),
                'Row #' . $i . ' (' . json_encode($v2Fixture[0]['value']) . ') : V1 Feature String'
            );
            $this->assertSame(
                $expectedResult,
                $accountFeaturesMask->isFeatureEnabled(GetFeaturesService::FEATURE_PRESERVE_LAYOUT, $v1Fixture, $v2Fixture),
                'Row #' . $i . ' (' . json_encode($v2Fixture[0]['value']) . ') : V2 Feature String'
            );
        }
    }

    public function testGetEnabledDisabledFeatures_Mapped_V1NotEnabled_V1NotDisabled_V2NotPresent()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar',
        ];
        $v2Fixture = [];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
                'preserves_layout',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Mapped_V1NotEnabled_V1NotDisabled_V2Disabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar',
        ];
        $v2Fixture = [
            [
                'key' => 's.preservedlayout',
                'value' => 'no',
            ],
        ];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
                'preserves_layout',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Mapped_V1NotEnabled_V1NotDisabled_V2Enabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar',
        ];
        $v2Fixture = [
            [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
                'preserves_layout',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Mapped_V1NotEnabled_V1Disabled_V2NotPresent()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar,preserves_layout',
        ];
        $v2Fixture = [];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
                'preserves_layout',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Mapped_V1NotEnabled_V1Disabled_V2Disabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar,preserves_layout',
        ];
        $v2Fixture = [
            [
                'key' => 's.preservedlayout',
                'value' => 'no',
            ],
        ];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
                'preserves_layout',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Mapped_V1NotEnabled_V1Disabled_V2Enabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar,preserves_layout',
        ];
        $v2Fixture = [
            [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
                'preserves_layout',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Mapped_V1Enabled_V1NotDisabled_V2NotPresent()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo,preserves_layout',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar',
        ];
        $v2Fixture = [];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
                'preserves_layout',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Mapped_V1Enabled_V1NotDisabled_V2Disabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo,preserves_layout',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar',
        ];
        $v2Fixture = [
            [
                'key' => 's.preservedlayout',
                'value' => 'no',
            ],
        ];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
                'preserves_layout',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Mapped_V1Enabled_V1NotDisabled_V2Enabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo,preserves_layout',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar',
        ];
        $v2Fixture = [
            [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
                'preserves_layout',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Mapped_V1Enabled_V2Disabled_V2NotPresent()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo,preserves_layout',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar,preserves_layout',
        ];
        $v2Fixture = [];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
                'preserves_layout',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Mapped_V1Enabled_V1Disabled_V2Disabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo,preserves_layout',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar,preserves_layout',
        ];
        $v2Fixture = [
            [
                'key' => 's.preservedlayout',
                'value' => 'no',
            ],
        ];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
                'preserves_layout',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Mapped_V1Enabled_V1Disabled_V2Enabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => AccountFeaturesModel::PM_FEATUREFLAG_PRESERVES_LAYOUT,
                    'v2Flag' => GetFeaturesService::FEATURE_PRESERVE_LAYOUT,
                ],
            ],
        ]);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo,preserves_layout',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar,preserves_layout',
        ];
        $v2Fixture = [
            [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
                'preserves_layout',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Unmapped_V1NotEnabled_V1NotDisabled_V2Enabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar',
        ];
        $v2Fixture = [
            [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Unmapped_V1NotEnabled_V1Disabled_V2Enabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar,preserves_layout',
        ];
        $v2Fixture = [
            [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
                'preserves_layout'
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Unmapped_V1Enabled_V1NotDisabled_V2Enabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo,preserves_layout',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar',
        ];
        $v2Fixture = [
            [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
                'preserves_layout',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testGetEnabledDisabledFeatures_Unmapped_V1Enabled_V1Disabled_V2Enabled()
    {
        $this->setupPhp5();

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class);

        $v1Fixture = [
            'enabled' => 'enabledaddtocartfront,allowgroupprices,boosting,foo,preserves_layout',
            'disabled' => 'enabledcategorynavigation,enabledcmsfront,enabledpopulartermfront,bar,preserves_layout',
        ];
        $v2Fixture = [
            [
                'key' => 's.preservedlayout',
                'value' => 'yes',
            ],
        ];

        $expectedResult = [
            'enabled' => [
                'enabledaddtocartfront',
                'allowgroupprices',
                'boosting',
                'foo',
            ],
            'disabled' => [
                'enabledcategorynavigation',
                'enabledcmsfront',
                'enabledpopulartermfront',
                'bar',
                'preserves_layout', // Disabled takes priority when present in both and no v2 mapping
            ],
        ];

        $actualResult = [
            'enabled' => $accountFeaturesMask->getEnabledFeatures($v1Fixture, $v2Fixture),
            'disabled' => $accountFeaturesMask->getDisabledFeatures($v1Fixture, $v2Fixture),
        ];

        array_walk($expectedResult, static function (&$row) { natcasesort($row); });
        array_walk($actualResult, static function (&$row) { natcasesort($row); });

        $this->assertSame(array_values($expectedResult['enabled']), array_values($actualResult['enabled']), 'Enabled');
        $this->assertSame(array_values($expectedResult['disabled']), array_values($actualResult['disabled']), 'Disabled');
    }

    public function testConstructorArguments_MissingV1Flag()
    {
        $this->setupPhp5();

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->once())
            ->method('warning')
            ->with('v1Flag is a required value; skipping declaration');

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'logger' => $loggerMock,
            'v1Tov2FlagMap' => [
                [
                    'v1' => 'foo',
                    'v2Flag' => 'bar',
                ],
            ],
        ]);
    }

    public function testConstructorArguments_InvalidV1Flag_Type()
    {
        $this->setupPhp5();

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->once())
            ->method('warning')
            ->with('v1Flag must be a non-empty string; skipping declaration');

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'logger' => $loggerMock,
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => ['foo'],
                    'v2Flag' => 'bar',
                ],
            ],
        ]);
    }

    public function testConstructorArguments_InvalidV1Flag_Empty()
    {
        $this->setupPhp5();

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->once())
            ->method('warning')
            ->with('v1Flag must be a non-empty string; skipping declaration');

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'logger' => $loggerMock,
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => '  ',
                    'v2Flag' => 'bar',
                ],
            ],
        ]);
    }

    public function testConstructorArguments_MissingV2Flag()
    {
        $this->setupPhp5();

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->once())
            ->method('warning')
            ->with('v2Flag is a required value; skipping declaration');

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'logger' => $loggerMock,
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => 'foo',
                    'v2' => 'bar',
                ],
            ],
        ]);
    }

    public function testConstructorArguments_InvalidV2Flag_Type()
    {
        $this->setupPhp5();

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->once())
            ->method('warning')
            ->with('v2Flag must be null or a non-empty string; skipping declaration');

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'logger' => $loggerMock,
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => 'foo',
                    'v2Flag' => ['bar'],
                ],
            ],
        ]);
    }

    public function testConstructorArguments_InvalidV2Flag_Empty()
    {
        $this->setupPhp5();

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->once())
            ->method('warning')
            ->with('v2Flag must be null or a non-empty string; skipping declaration');

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'logger' => $loggerMock,
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => 'foo',
                    'v2Flag' => '   ',
                ],
            ],
        ]);
    }

    public function testConstructorArguments_Valid()
    {
        $this->setupPhp5();

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->never())
            ->method('warning');

        /** @var AccountFeaturesMask $accountFeaturesMask */
        $accountFeaturesMask = $this->objectManager->getObject(AccountFeaturesMask::class, [
            'logger' => $loggerMock,
            'v1Tov2FlagMap' => [
                [
                    'v1Flag' => ' foo ',
                    'v2Flag' => 'bar',
                ],
                [
                    'v1Flag' => 'foo',
                    'v2Flag' => null,
                ],
                [
                    'v2Flag' => ' wom ',
                    'v1Flag' => 'bat',
                ],
            ],
        ]);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = new ObjectManager($this);
    }
}
