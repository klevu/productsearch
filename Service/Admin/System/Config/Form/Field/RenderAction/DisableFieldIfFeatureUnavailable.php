<?php

namespace Klevu\Search\Service\Admin\System\Config\Form\Field\RenderAction;

use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Api\Service\Admin\SystemConfigFormFieldRenderActionInterface;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Psr\Log\LoggerInterface;

class DisableFieldIfFeatureUnavailable implements SystemConfigFormFieldRenderActionInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetFeaturesInterface
     */
    private $getFeatures;

    /**
     * @var string[]
     */
    private $fieldToFeatureMap = [];

    /**
     * @param LoggerInterface $logger
     * @param GetFeaturesInterface $getFeatures
     * @param string[] $fieldToFeatureMap
     */
    public function __construct(
        LoggerInterface $logger,
        GetFeaturesInterface $getFeatures,
        array $fieldToFeatureMap = []
    ) {
        $this->logger = $logger;
        $this->getFeatures = $getFeatures;
        $this->setFieldToFeatureMap($fieldToFeatureMap);
    }

    /**
     * @param array $fieldToFeatureMap
     * @return void
     */
    private function setFieldToFeatureMap(array $fieldToFeatureMap)
    {
        foreach ($fieldToFeatureMap as $field => $feature) {
            if (!is_string($feature) || !trim($feature)) {
                $this->logger->warning(sprintf(
                    'Invalid feature for fieldToFeatureMap "%s"; expected non-empty string, received %s',
                    $field,
                    is_object($feature) ? get_class($feature) : gettype($feature)
                ));
                continue;
            }

            $this->fieldToFeatureMap[$field] = trim($feature);
        }
    }

    /**
     * @param Field $field
     * @param AbstractElement $element
     * @return bool
     */
    public function applies(Field $field, AbstractElement $element)
    {
        if (!array_key_exists($element->getHtmlId(), $this->fieldToFeatureMap)) {
            return false;
        }

        $accountFeatures = $this->getFeatures->execute();

        return $accountFeatures
            && !$accountFeatures->isFeatureAvailable($this->fieldToFeatureMap[$element->getHtmlId()], true);
    }

    /**
     * @param Field $field
     * @param AbstractElement $element
     * @return AbstractElement[]
     */
    public function beforeRender(Field $field, AbstractElement $element)
    {
        $element->setData('disabled', true);
        $element->setData('can_use_default_value', false);
        $element->setData('can_use_website_value', false);

        $accountFeatures = $this->getFeatures->execute();
        if ($accountFeatures->getUpgradeMessage()) {
            $element->setData('label', sprintf(
                '%s <div class="klevu-upgrade-block">%s</div>',
                $element->getData('label'),
                $accountFeatures->getUpgradeMessage()
            ));
        }

        return [$element];
    }

    /**
     * @param Field $field
     * @param mixed $result
     * @param AbstractElement $element
     * @return mixed
     */
    public function afterRender(Field $field, $result, AbstractElement $element)
    {
        return $result;
    }
}
