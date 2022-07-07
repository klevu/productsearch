<?php

namespace Klevu\Search\Service\Account\Model;

use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;

class AccountFeatures implements AccountFeaturesInterface
{
    const PM_FEATUREFLAG_ADD_TO_CART = 'enabledaddtocartfront';
    const PM_FEATUREFLAG_ALLOW_GROUP_PRICES = 'allowgroupprices';
    const PM_FEATUREFLAG_BOOSTING = 'boosting';
    const PM_FEATUREFLAG_CATEGORY_NAVIGATION = 'enabledcategorynavigation';
    const PM_FEATUREFLAG_CMS_FRONT = 'enabledcmsfront';
    const PM_FEATUREFLAG_PRESERVES_LAYOUT = 'preserves_layout';
    const PM_FEATUREFLAG_POPULAR_TERM = 'enabledpopulartermfront';
    const PM_FEATUREFLAG_RECOMMENDATIONS = 'enabledrecommendations';

    /**
     * @var string
     */
    private $upgradeUrl;
    /**
     * @var string
     */
    private $upgradeMessage;
    /**
     * @var string
     */
    private $upgradeLabel;
    /**
     * @var string
     */
    private $preserveLayoutMessage;
    /**
     * @var string[]
     */
    private $enabledFeatures = [];
    /**
     * @var string[]
     */
    private $disabledFeatures = [];
    /**
     * @var string
     */
    private $userPlanForStore;

    /**
     * @return string
     */
    public function getUpgradeUrl()
    {
        return $this->upgradeUrl;
    }

    /**
     * @param string $upgradeUrl
     */
    public function setUpgradeUrl($upgradeUrl)
    {
        $this->upgradeUrl = $upgradeUrl;
    }

    /**
     * @return string
     */
    public function getUpgradeMessage()
    {
        return $this->upgradeMessage;
    }

    /**
     * @param string $upgradeMessage
     */
    public function setUpgradeMessage($upgradeMessage)
    {
        $this->upgradeMessage = $upgradeMessage;
    }

    /**
     * @return string
     */
    public function getUpgradeLabel()
    {
        return $this->upgradeLabel;
    }

    /**
     * @param string $upgradeLabel
     */
    public function setUpgradeLabel($upgradeLabel)
    {
        $this->upgradeLabel = $upgradeLabel;
    }

    /**
     * @return string
     */
    public function getPreserveLayoutMessage()
    {
        return $this->preserveLayoutMessage;
    }

    /**
     * @param string $preserveLayoutMessage
     */
    public function setPreserveLayoutMessage($preserveLayoutMessage)
    {
        $this->preserveLayoutMessage = $preserveLayoutMessage;
    }

    /**
     * @return string[]
     */
    public function getEnabledFeatures()
    {
        return $this->enabledFeatures;
    }

    /**
     * @param string[] $enabledFeatures
     */
    public function setEnabledFeatures(array $enabledFeatures)
    {
        $this->enabledFeatures = $enabledFeatures;
    }

    /**
     * @param string $feature
     *
     * @return bool
     */
    public function isFeatureEnabled($feature)
    {
        return in_array(trim($feature), $this->getEnabledFeatures(), true);
    }

    /**
     * @return string[]
     */
    public function getDisabledFeatures()
    {
        return $this->disabledFeatures;
    }

    /**
     * @param string[] $disabledFeatures
     */
    public function setDisabledFeatures(array $disabledFeatures)
    {
        $this->disabledFeatures = $disabledFeatures;
    }

    /**
     * @param string $feature
     *
     * @return bool
     */
    public function isFeatureDisabled($feature)
    {
        return in_array(trim($feature), $this->getDisabledFeatures(), true);
    }

    /**
     * @param string $feature
     * @param bool $strict
     *
     * @return bool
     */
    public function isFeatureAvailable($feature, $strict = false)
    {
        if ($strict) {
            // When in strict mode, the feature must be enabled and _not_ disabled
            return $this->isFeatureEnabled($feature) && !$this->isFeatureDisabled($feature);
        }
        // When not in strict mode, it's sufficient for the feature to either be enabled
        //  or for there to be disabled features which do not include the feature in question
        // This is to retain backward-compatibility with the way CatNav was originally checked
        return $this->isFeatureEnabled($feature)
            || (!empty($this->getDisabledFeatures()) && !$this->isFeatureDisabled($feature));
    }

    /**
     * @return string
     */
    public function getUserPlanForStore()
    {
        return $this->userPlanForStore;
    }

    /**
     * @param string $userPlanForStore
     */
    public function setUserPlanForStore($userPlanForStore)
    {
        $this->userPlanForStore = $userPlanForStore;
    }
}
