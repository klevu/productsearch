<?php

namespace Klevu\Search\Api\Service\Account;

interface AccountFeaturesMaskInterface
{
    /**
     * @param string $featureString Either v1 or v2 string
     * @param string[] $featuresV1
     * @param string[] $featuresV2
     * @return bool|null
     */
    public function isFeatureEnabled($featureString, array $featuresV1, array $featuresV2);

    /**
     * @param string[] $featuresV1
     * @param string[] $featuresV2
     * @return string[] Returns v1 flag string
     */
    public function getEnabledFeatures(array $featuresV1, array $featuresV2);

    /**
     * @param string[] $featuresV1
     * @param string[] $featuresV2
     * @return string[] Returns v1 flag strings
     */
    public function getDisabledFeatures(array $featuresV1, array $featuresV2);
}
