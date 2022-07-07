<?php

namespace Klevu\Search\Plugin\Admin\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

class HideLabelForSingleStoreModePlugin
{
    const GENERAL_SINGLE_STORE_MODE_ENABLED = 'general/single_store_mode/enabled';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var array
     */
    private $labelsToHide;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        array $labelsToHide = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->labelsToHide = $labelsToHide;
    }

    /**
     * @param Field $subject
     * @param AbstractElement $element
     *
     * @return array
     */
    public function beforeRender(Field $subject, AbstractElement $element)
    {
        if ($this->isLabelToBeHidden($element)) {
            $element->unsetData();
        }

        return [$element];
    }

    /**
     * @param AbstractElement $element
     *
     * @return bool
     */
    private function isLabelToBeHidden(AbstractElement $element)
    {
        if ('label' !== $element->getType()) {
            return false;
        }
        if (!in_array($element->getData('html_id'), $this->labelsToHide, true)) {
            return false;
        }

        return $this->scopeConfig->isSetFlag(static::GENERAL_SINGLE_STORE_MODE_ENABLED);
    }
}
