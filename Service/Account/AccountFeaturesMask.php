<?php

namespace Klevu\Search\Service\Account;

use Klevu\Search\Api\Service\Account\AccountFeaturesMaskInterface;
use Klevu\Search\Service\Account\Model\AccountFeatures;
use Psr\Log\LoggerInterface;

class AccountFeaturesMask implements AccountFeaturesMaskInterface
{
    /**
     * Values which getFeatureValues will return which should signify a feature is enabled
     * Note that "enabled" is intentionally omitted
     *
     * @var string[]
     */
    private static $acceptedV2EnabledFlagValues = [
        'yes',
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Placeholders for unmapped flags added for performance during lookups
     * Actual mapping performed in relevant modules' di.xml
     *
     * @var string[]|null[]
     */
    private $v1ToV2FlagMapHash = [
        AccountFeatures::PM_FEATUREFLAG_ADD_TO_CART => null,
        AccountFeatures::PM_FEATUREFLAG_ALLOW_GROUP_PRICES => null,
        AccountFeatures::PM_FEATUREFLAG_BOOSTING => null,
        AccountFeatures::PM_FEATUREFLAG_CMS_FRONT => null,
        AccountFeatures::PM_FEATUREFLAG_POPULAR_TERM => null,
    ];

    /**
     * @var array[]
     */
    private $featuresCache = [];

    /**
     * @param LoggerInterface $logger
     * @param array[] $v1Tov2FlagMap
     */
    public function __construct(
        LoggerInterface $logger,
        array $v1Tov2FlagMap = []
    ) {
        $this->logger = $logger;
        $this->v1ToV2FlagMapHash = array_merge(
            $this->v1ToV2FlagMapHash,
            $this->convertV1ToV2FlagMapToHash($v1Tov2FlagMap)
        );
    }

    /**
     * @param string $featureString Either v1 or v2 string
     * @param string[] $featuresV1
     * @param string[][] $featuresV2
     * @return bool|null
     */
    public function isFeatureEnabled($featureString, array $featuresV1, array $featuresV2)
    {
        $this->validateFeatureString($featureString);

        // Check and update featureString if a v2 string is provided
        if (!isset($this->v1ToV2FlagMapHash[$featureString])) {
            $v1FeatureString = array_search($featureString, $this->v1ToV2FlagMapHash, true);
            if (is_string($v1FeatureString)) {
                $featureString = $v1FeatureString;
            }
        }

        if (!isset($this->v1ToV2FlagMapHash[$featureString])) { // isset ignores null intentionally
            $isEnabledV1 = in_array($featureString, $this->getEnabledV1FeatureFlagsAsArray($featuresV1), true);
            $isDisabledV1 = in_array($featureString, $this->getDisabledV1FeatureFlagsAsArray($featuresV1), true);

            // Unknown string. Return null gives us a way to identify, but also evaluates as falsy
            if (!$isEnabledV1 && !$isDisabledV1) {
                return null;
            }

            // If we don't have mapping for the v2 flag use the v1 data, with "disabled" working as a veto
            return $isEnabledV1 && !$isDisabledV1;
        }

        // This is a mapped field, regardless of whether it is present in the v2 flags received
        // Here, we always use the v2 value, ignoring v1 above
        $v2FeaturesHash = $this->getV2FeatureFlagsAsHash($featuresV2);

        return isset($v2FeaturesHash[$this->v1ToV2FlagMapHash[$featureString]]) // Must be present...
            && in_array( // ... and one of the whitelisted values
                $v2FeaturesHash[$this->v1ToV2FlagMapHash[$featureString]],
                static::$acceptedV2EnabledFlagValues,
                true
            );
    }

    /**
     * @param string[] $featuresV1
     * @param string[] $featuresV2
     * @return string[] Returns v1 flag string
     */
    public function getEnabledFeatures(array $featuresV1, array $featuresV2)
    {
        $features = $this->getFeatures($featuresV1, $featuresV2);

        return $features['enabled'];
    }

    /**
     * @param string[] $featuresV1
     * @param string[] $featuresV2
     * @return string[] Returns v1 flag strings
     */
    public function getDisabledFeatures(array $featuresV1, array $featuresV2)
    {
        $features = $this->getFeatures($featuresV1, $featuresV2);

        return $features['disabled'];
    }

    /**
     * @param array $featuresV1
     * @param array $featuresV2
     * @return array[]
     */
    private function getFeatures(array $featuresV1, array $featuresV2)
    {
        $cacheKey = (string)crc32(json_encode($featuresV1) . ':' . json_encode($featuresV2));
        if (!isset($this->featuresCache[$cacheKey])) {
            $allFeatureStrings = array_unique(array_merge(
                array_keys($this->v1ToV2FlagMapHash),
                $this->getEnabledV1FeatureFlagsAsArray($featuresV1),
                $this->getDisabledV1FeatureFlagsAsArray($featuresV1)
            ));

            $this->featuresCache[$cacheKey] = [
                'enabled' => array_filter(
                    $allFeatureStrings,
                    function ($featureString) use ($featuresV1, $featuresV2) {
                        $this->validateFeatureString($featureString);

                        return $this->isFeatureEnabled($featureString, $featuresV1, $featuresV2);
                    }
                ),
            ];
            $this->featuresCache[$cacheKey]['disabled'] = array_diff(
                $allFeatureStrings,
                $this->featuresCache[$cacheKey]['enabled']
            );
        }

        return $this->featuresCache[$cacheKey];
    }

    /**
     * @param string[][]|null[][] $v1Tov2FlagMap
     * @return string[]|null[]
     */
    private function convertV1ToV2FlagMapToHash(array $v1Tov2FlagMap)
    {
        $return = [];
        foreach ($v1Tov2FlagMap as $item) {
            if (!array_key_exists('v1Flag', $item)) {
                $this->logger->warning('v1Flag is a required value; skipping declaration', [
                    'v1Tov2FlagMap' => $v1Tov2FlagMap,
                ]);
                continue;
            }
            if (!is_string($item['v1Flag']) || '' === trim($item['v1Flag'])) {
                $this->logger->warning('v1Flag must be a non-empty string; skipping declaration', [
                    'v1Tov2FlagMap' => $v1Tov2FlagMap,
                ]);
                continue;
            }

            if (!array_key_exists('v2Flag', $item)) {
                $this->logger->warning('v2Flag is a required value; skipping declaration', [
                    'v1Tov2FlagMap' => $v1Tov2FlagMap,
                ]);
                continue;
            }
            if (null !== $item['v2Flag'] && (!is_string($item['v2Flag']) || '' === trim($item['v2Flag']))) {
                $this->logger->warning('v2Flag must be null or a non-empty string; skipping declaration', [
                    'v1Tov2FlagMap' => $v1Tov2FlagMap,
                ]);
                continue;
            }

            $return[trim($item['v1Flag'])] = is_string($item['v2Flag'])
                ? trim($item['v2Flag'])
                : null;
        }

        return $return;
    }

    /**
     * @param mixed $featureString
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateFeatureString($featureString)
    {
        if (!is_string($featureString) || !trim($featureString)) {
            throw new \InvalidArgumentException(sprintf(
                'featuresString must be non-empty string; %s received',
                //phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                is_object($featureString) ? get_class($featureString) : gettype($featureString)
            ));
        }
    }

    /**
     * @param array $featuresV1
     * @return string[]
     */
    private function getEnabledV1FeatureFlagsAsArray(array $featuresV1)
    {
        $enabledV1FeaturesString = isset($featuresV1['enabled']) && !is_array($featuresV1['enabled'])
            ? $featuresV1['enabled'] :
            '';

        return array_filter(
            array_map(
                'trim',
                explode(',', $enabledV1FeaturesString)
            )
        );
    }

    /**
     * @param array $featuresV1
     * @return string[]
     */
    private function getDisabledV1FeatureFlagsAsArray(array $featuresV1)
    {
        $disabledV1FeaturesString = isset($featuresV1['disabled']) && !is_array($featuresV1['disabled'])
            ? $featuresV1['disabled']
            : '';

        return array_filter(
            array_map(
                'trim',
                explode(',', $disabledV1FeaturesString)
            )
        );
    }

    /**
     * @param array $featuresV2
     * @return array
     */
    private function getV2FeatureFlagsAsHash(array $featuresV2)
    {
        return array_map(
            static function ($value) {
                return is_string($value)
                    ? strtolower(trim($value))
                    : $value;
            },
            array_column(
                $featuresV2,
                'value',
                'key'
            )
        );
    }
}
