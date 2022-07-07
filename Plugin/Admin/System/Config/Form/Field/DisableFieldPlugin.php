<?php

namespace Klevu\Search\Plugin\Admin\System\Config\Form\Field;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;
use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class DisableFieldPlugin
{
    const SEARCH_LANDING_PRESERVE_LAYOUT = \Klevu\Search\Model\System\Config\Source\Landingoptions::YES;
    const SEARCH_LANDING_KLEVU_JS_THEME = \Klevu\Search\Model\System\Config\Source\Landingoptions::KlEVULAND;

    /**
     * @var GetFeaturesInterface
     */
    private $getFeatures;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ScopeConfigWriterInterface
     */
    private $scopeConfigWriter;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var StoreInterface
     */
    private $store;
    /**
     * @var array
     */
    private $sectionsToDisable;
    /**
     * @var array
     */
    private $groupsToDisable;
    /**
     * @var array
     */
    private $fieldsToDisable;

    public function __construct(
        GetFeaturesInterface $getFeatures,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        ScopeConfigWriterInterface $scopeConfigWriter,
        StoreManagerInterface $storeManager,
        array $sectionsToDisable = [],
        array $groupsToDisable = [],
        array $fieldsToDisable = []
    ) {
        $this->getFeatures = $getFeatures;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->scopeConfigWriter = $scopeConfigWriter;
        $this->storeManager = $storeManager;
        $this->sectionsToDisable = $sectionsToDisable;
        $this->groupsToDisable = $groupsToDisable;
        $this->fieldsToDisable = $fieldsToDisable;
    }

    /**
     * @param Field $subject
     * @param AbstractElement $element
     *
     * @return AbstractElement[]
     */
    public function beforeRender(Field $subject, AbstractElement $element)
    {
        if (!$this->sectionsToDisable && !$this->groupsToDisable && !$this->fieldsToDisable) {
            return [$element];
        }
        $configPathArray = $this->getConfigPath($element);
        if (
            !$configPathArray ||
            empty($configPathArray['section']) || empty($configPathArray['group']) || empty($configPathArray['field'])
        ) {
            return [$element];
        }
        if (!$featureToCheck = $this->getFeatureToCheck($configPathArray)) {
            return [$element];
        }

        $accountFeatures = $this->getFeatures->execute();
        if (!$accountFeatures->isFeatureAvailable($featureToCheck, true)) {
            $element->setData('disabled', true);
            $element->setData('value', 0);
            $element->setData('can_use_default_value', false);
            $element->setData('can_use_website_value', false);
            $configPath = $configPathArray['section'] . '/' . $configPathArray['group'] . '/' . $configPathArray['field'];
            $this->setCoreConfigValue($configPath);
            $element = $this->addUpgradeMessagingToLabel($element, $accountFeatures);
        }

        return [$element];
    }

    /**
     * @param AbstractElement $element
     *
     * @return array
     */
    private function getConfigPath(AbstractElement $element)
    {
        $container = $element->getContainer();
        if (!$container || !$container->getGroup()) {
            return [];
        }
        $containerHtmlId = $container->getHtmlId();

        $group = $container->getGroup();
        $path = isset($group['path']) ? $group['path'] : '';

        $configPathArray = [];
        $configPathArray['section'] = $path;
        $configPathArray['group'] = str_replace($path . "_", '', $containerHtmlId);
        $configPathArray['field'] = str_replace($containerHtmlId . '_', '', $element->getHtmlId());

        return $configPathArray;
    }

    /**
     * @param array $configPathArray
     *
     * @return mixed|null
     */
    private function getFeatureToCheck(array $configPathArray)
    {
        $featureToCheck = null;
        $sectionKeys = array_keys($this->sectionsToDisable);
        $groupKeys = array_keys($this->groupsToDisable);
        $fieldKeys = array_keys($this->fieldsToDisable);

        $sectionPath = $configPathArray['section'];
        $groupPath = $configPathArray['section'] . '_' . $configPathArray['group'];
        $fieldPath = $configPathArray['section'] . '_' . $configPathArray['group'] . '_' . $configPathArray['field'];

        if (in_array($sectionPath, $sectionKeys, true)) {
            $featureToCheck = $this->sectionsToDisable[$sectionPath];
        } elseif (in_array($groupPath, $groupKeys, true)) {
            $featureToCheck = $this->groupsToDisable[$groupPath];
        } elseif (in_array($fieldPath, $fieldKeys, true)) {
            $featureToCheck = $this->fieldsToDisable[$fieldPath];
        }

        return $featureToCheck;
    }

    /**
     * @return void
     */
    private function setCoreConfigValue($configPath)
    {
        $storeParam = $this->request->getParam('store');
        try {
            $store = $this->storeManager->getStore($storeParam);
            $storeId = $store->getId();
        } catch (NoSuchEntityException $exception) {
            return;
        }
        if (!$currentValue = $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORES, $storeId)) {
            return;
        }
        if ($this->ifSearchLandingIsPreserveLayout($configPath, $currentValue)) {
            $this->saveConfig($configPath, self::SEARCH_LANDING_KLEVU_JS_THEME, $storeId);

            return;
        }
        $this->saveConfig($configPath, 0, $storeId);
    }

    /**
     * @param string $configPath
     * @param string $currentValue
     *
     * @return bool
     */
    private function ifSearchLandingIsPreserveLayout($configPath, $currentValue)
    {
        return $configPath === ConfigHelper::XML_PATH_LANDING_ENABLED &&
            $currentValue === self::SEARCH_LANDING_PRESERVE_LAYOUT;
    }

    /**
     * @param string $configPath
     * @param $value
     * @param int $storeId
     *
     * @return void
     */
    private function saveConfig($configPath, $value, $storeId)
    {
        if (!$configPath) {
            throw new InvalidArgumentException('Invalid Config Path');
        }
        $this->scopeConfigWriter->save(
            $configPath,
            $value,
            ScopeInterFace::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * @param AbstractElement $element
     * @param AccountFeaturesInterface $accountFeatures
     *
     * @return AbstractElement
     */
    private function addUpgradeMessagingToLabel(AbstractElement $element, AccountFeaturesInterface $accountFeatures)
    {
        if ($accountFeatures->getUpgradeMessage()) {
            $html = "<div class='klevu-upgrade-block'>";
            $html .= $accountFeatures->getUpgradeMessage();
            $html .= "</div>";
            $element->setData('label', $element->getLabel() . ' ' . $html);
        }

        return $element;
    }
}
