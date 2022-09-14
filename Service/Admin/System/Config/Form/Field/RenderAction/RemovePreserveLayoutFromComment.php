<?php

namespace Klevu\Search\Service\Admin\System\Config\Form\Field\RenderAction;

use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Api\Service\Admin\SystemConfigFormFieldRenderActionInterface;
use Klevu\Search\Service\Account\Model\AccountFeatures;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Psr\Log\LoggerInterface;

class RemovePreserveLayoutFromComment implements SystemConfigFormFieldRenderActionInterface
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
     * @var array
     */
    private $fieldIds = [];

    /**
     * @param LoggerInterface $logger
     * @param GetFeaturesInterface $getFeatures
     * @param array $fieldIds
     */
    public function __construct(
        LoggerInterface $logger,
        GetFeaturesInterface $getFeatures,
        array $fieldIds = []
    ) {
        $this->logger = $logger;
        $this->getFeatures = $getFeatures;
        $this->setFieldIds($fieldIds);
    }

    /**
     * @param array $fieldIds
     * @return void
     */
    private function setFieldIds(array $fieldIds)
    {
        foreach ($fieldIds as $key => $fieldId) {
            if (!is_string($fieldId) || !trim($fieldId)) {
                $this->logger->warning(sprintf(
                    'Invalid feature for fieldIds "%s"; expected non-empty string, received %s',
                    $key,
                    is_object($fieldId) ? get_class($fieldId) : gettype($fieldId)
                ));
                continue;
            }

            $this->fieldIds[$key] = trim($fieldId);
        }
    }

    /**
     * @param Field $field
     * @param AbstractElement $element
     * @return bool
     */
    public function applies(Field $field, AbstractElement $element)
    {
        if (!in_array($element->getHtmlId(), $this->fieldIds, true)) {
            return false;
        }

        $accountFeatures = $this->getFeatures->execute();

        return $accountFeatures
            && !$accountFeatures->isFeatureAvailable(AccountFeatures::PM_FEATUREFLAG_PRESERVES_LAYOUT, true);
    }


    /**
     * @param Field $field
     * @param AbstractElement $element
     * @return AbstractElement[]
     */
    public function beforeRender(Field $field, AbstractElement $element)
    {
        $comment = preg_replace(
            '#(<br\s?/?>\s*)*<span class="preserve-layout-comment">.*?</span>#s',
            '',
            (string)$element->getDataUsingMethod('comment')
        );

        if ($comment) {
            $element->setDataUsingMethod('comment', $comment);
        } else {
            $element->unsetData('comment');
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
