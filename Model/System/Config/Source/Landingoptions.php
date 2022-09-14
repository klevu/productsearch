<?php

namespace Klevu\Search\Model\System\Config\Source;

use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Service\Account\Model\AccountFeatures;
use Magento\Framework\App\ObjectManager;

class Landingoptions
{
    const YES = 1;
    const NO = 0;
    const KlEVULAND = 2;

    /**
     * @var GetFeaturesInterface
     */
    private $getFeatures;

    /**
     * @var array[]
     */
    private $options;

    /**
     * @param GetFeaturesInterface|null $getFeatures
     */
    public function __construct(GetFeaturesInterface $getFeatures = null)
    {
        $this->getFeatures = $getFeatures ?: ObjectManager::getInstance()->get(GetFeaturesInterface::class);
    }

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        if (null === $this->options) {
            $accountFeatures = $this->getFeatures->execute();

            $this->options = [
                ['value' => static::NO, 'label' => __('Native')],
            ];

            if ($accountFeatures && $accountFeatures->isFeatureAvailable(AccountFeatures::PM_FEATUREFLAG_PRESERVES_LAYOUT)) {
                $this->options[] = ['value' => static::KlEVULAND, 'label' => __('Klevu JS Theme (Recommended)')];
                $this->options[] = ['value' => static::YES, 'label' => __('Preserve your Magento layout')];
            } else {
                $this->options[] = ['value' => static::KlEVULAND, 'label' => __('Klevu JS Theme')];
            }
        }

        return $this->options;
    }
}
