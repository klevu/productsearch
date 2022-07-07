<?php

namespace Klevu\Search\Api\Service\Account\Model;

interface AccountFeaturesInterface
{
    /**
     * @return string
     */
    public function getUpgradeUrl();

    /**
     * @param string $upgradeUrl
     */
    public function setUpgradeUrl($upgradeUrl);

    /**
     * @return string
     */
    public function getUpgradeMessage();

    /**
     * @param string $upgradeMessage
     */
    public function setUpgradeMessage($upgradeMessage);

    /**
     * @return string
     */
    public function getUpgradeLabel();

    /**
     * @param string $upgradeLabel
     */
    public function setUpgradeLabel($upgradeLabel);

    /**
     * @return string
     */
    public function getPreserveLayoutMessage();

    /**
     * @param string $preserveLayoutMessage
     */
    public function setPreserveLayoutMessage($preserveLayoutMessage);

    /**
     * @return string[]
     */
    public function getEnabledFeatures();

    /**
     * @param string[] $enabledFeatures
     */
    public function setEnabledFeatures(array $enabledFeatures);

    /**
     * @param string $feature
     *
     * @return bool
     */
    public function isFeatureEnabled($feature);

    /**
     * @return string[]
     */
    public function getDisabledFeatures();

    /**
     * @param string[] $disabledFeatures
     */
    public function setDisabledFeatures(array $disabledFeatures);

    /**
     * @param string $feature
     *
     * @return bool
     */
    public function isFeatureDisabled($feature);

    /**
     * @param string $feature
     * @param bool $strict
     *
     * @return bool
     */
    public function isFeatureAvailable($feature, $strict = false);

    /**
     * @return string
     */
    public function getUserPlanForStore();

    /**
     * @param string $userPlanForStore
     */
    public function setUserPlanForStore($userPlanForStore);
}
