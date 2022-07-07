<?php

namespace Klevu\Search\Plugin\Admin\System\Config\Form\Field;

use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;
use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

class AddSubscriptionMessagePlugin
{
    /**
     * @var GetFeaturesInterface
     */
    private $getFeatures;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var AccountFeaturesInterface
     */
    private $accountFeatures;
    /**
     * @var array
     */
    private $fieldsToShowMessageFor;

    public function __construct(
        GetFeaturesInterface $getFeatures,
        RequestInterface $request,
        array $fieldsToShowMessageFor = []
    ) {
        $this->getFeatures = $getFeatures;
        $this->request = $request;
        $this->fieldsToShowMessageFor = $fieldsToShowMessageFor;
    }

    /**
     * @param Field $subject
     * @param AbstractElement $element
     *
     * @return array
     */
    public function beforeRender(Field $subject, AbstractElement $element)
    {
        if (!$this->fieldsToShowMessageFor) {
            return [$element];
        }
        if (!$featureToCheck = $this->getFeatureToCheck($element->getId())) {
            return [$element];
        }
        $this->initAccountFeatures();
        if (!$this->accountFeatures->isFeatureAvailable($featureToCheck, true)) {
            $element->setComment($this->getSubscriptionComment());
        }

        return [$element];
    }

    /**
     * @param string $elementId
     *
     * @return string
     */
    private function getFeatureToCheck($elementId)
    {
        $arrayKeys = array_keys($this->fieldsToShowMessageFor);
        if (in_array($elementId, $arrayKeys, true)) {
            return $this->fieldsToShowMessageFor[$elementId];
        }

        return '';
    }

    /**
     * @return void
     */
    private function initAccountFeatures()
    {
        $store = $this->request->getParam('store');
        $this->accountFeatures = $this->getFeatures->execute($store);
    }

    /**
     * @return string
     */
    private function getSubscriptionComment()
    {
        return "<div class='klevu-upgrade-block-simple'>" .
            $this->accountFeatures->getPreserveLayoutMessage() .
            "</div>";
    }
}
