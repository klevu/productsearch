<?php

namespace Klevu\Search\Service\Admin\System\Config\Form\Field\RenderAction;

use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;
use Klevu\Search\Api\Service\Admin\SystemConfigFormFieldRenderActionInterface;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class UpdateConfigValueIfFeatureUnavailable implements SystemConfigFormFieldRenderActionInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ScopeConfigWriterInterface
     */
    private $scopeConfigWriter;

    /**
     * @var GetFeaturesInterface
     */
    private $getFeatures;

    /**
     * @var array[]
     */
    private $fieldUpdateConfig = [];

    /**
     * @var AccountFeaturesInterface|null
     */
    private $accountFeatures;

    /**
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ScopeConfigWriterInterface $scopeConfigWriter
     * @param GetFeaturesInterface $getFeatures
     * @param array $fieldUpdateConfig
     */
    public function __construct(
        LoggerInterface $logger,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ScopeConfigWriterInterface $scopeConfigWriter,
        GetFeaturesInterface $getFeatures,
        array $fieldUpdateConfig = []
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->scopeConfigWriter = $scopeConfigWriter;
        $this->getFeatures = $getFeatures;
        $this->setFieldUpdateConfig($fieldUpdateConfig);
    }

    /**
     * @param array $fieldUpdateConfig
     * @return void
     */
    private function setFieldUpdateConfig(array $fieldUpdateConfig)
    {
        foreach ($fieldUpdateConfig as $key => $config) {
            if (!isset($config['element_id']) || !is_string($config['element_id']) || !trim($config['element_id'])) {
                $this->logger->warning(sprintf(
                    'Invalid field update config "%s", element_id key must be a non-empty string',
                    $key
                ), ['config' => $config]);

                continue;
            }

            if (!isset($config['feature']) || !is_string($config['feature']) || !trim($config['feature'])) {
                $this->logger->warning(sprintf(
                    'Invalid field update config "%s", feature key must be a non-empty string',
                    $key
                ), ['config' => $config]);

                continue;
            }

            if (!array_key_exists('value', $config)) {
                $this->logger->warning(sprintf(
                    'Invalid field update config "%s", value key must be present',
                    $key
                ), ['config' => $config]);

                continue;
            }

            if (!isset($config['allowed_values'])) {
                $config['allowed_values'] = [];
            }
            if (!is_array($config['allowed_values'])) {
                $this->logger->warning(sprintf(
                    'Invalid field update config "%s", allowed_values must be array',
                    $key
                ), ['config' => $config]);

                continue;
            }

            $config['allowed_values'] = array_unique(array_merge(
                $config['allowed_values'],
                [$config['value']]
            ));

            if (!isset($this->fieldUpdateConfig[$config['element_id']])) {
                $this->fieldUpdateConfig[$config['element_id']] = [];
            }

            $this->fieldUpdateConfig[$config['element_id']][$key] = $config;
            ksort($this->fieldUpdateConfig[$config['element_id']]);
        }
    }

    /**
     * @param Field $field
     * @param AbstractElement $element
     * @return bool
     */
    public function applies(Field $field, AbstractElement $element)
    {
        if (!array_key_exists($element->getHtmlId(), $this->fieldUpdateConfig)) {
            return false;
        }

        array_walk(
            $this->fieldUpdateConfig[$element->getHtmlId()],
            function (array &$fieldUpdateConfig) {
                $fieldUpdateConfig['applies'] = $this->appliesForFieldUpdateConfig($fieldUpdateConfig);
            }
        );

        foreach ($this->fieldUpdateConfig[$element->getHtmlId()] as $fieldUpdateConfig) {
            if ($fieldUpdateConfig['applies']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $fieldUpdateConfig
     * @return bool
     */
    private function appliesForFieldUpdateConfig(array $fieldUpdateConfig)
    {
        if (!isset($fieldUpdateConfig['feature'])) {
            return false;
        }

        $accountFeatures = $this->getAccountFeatures();
        if (!$accountFeatures) {
            return false;
        }

        return !$accountFeatures->isFeatureAvailable($fieldUpdateConfig['feature'], true);
    }

    /**
     * @param Field $field
     * @param AbstractElement $element
     * @return AbstractElement[]
     */
    public function beforeRender(Field $field, AbstractElement $element)
    {
        $storeParam = $this->request->getParam('store');
        if (!$storeParam) {
            return [$element];
        }

        $configPath = $this->getConfigPath($element);
        $allowedValues = [];
        foreach ($this->fieldUpdateConfig[$element->getHtmlId()] as $fieldUpdateConfig) {
            if (!isset($fieldUpdateConfig['applies'])) {
                $fieldUpdateConfig['applies'] = $this->appliesForFieldUpdateConfig($fieldUpdateConfig);
            }

            if (!$fieldUpdateConfig['applies']) {
                continue;
            }

            $configValue = $fieldUpdateConfig['value'];
            $allowedValues = $fieldUpdateConfig['allowed_values'];

            if (!in_array($element->getData('value'), $allowedValues, false)) {
                // We set this before store checks relating to saving to the db intentionally
                //  Even if there is an error saving automatically, the field value shown to the user
                //  is what we want them to save. Also, if we have removed an option from a select field
                //  we know it's going to display what we want.
                // If, however, there is no store param at all, we are in the wrong scope and shouldn't
                //  (in theory) be modifying the value
                $element->setData('value', $configValue);
            }
        }

        try {
            $store = $this->storeManager->getStore($storeParam);
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage(), [
                'configPath' => $configPath,
                'configValue' => $element->getData('value'),
                'storeParam' => $storeParam,
                'method' => __METHOD__,
            ]);

            return [$element];
        }

        $currentValue = $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORES,
            (int)$store->getId()
        );

        if (in_array($currentValue, $allowedValues, false)) {
            return [$element];
        }

        try {
            $this->scopeConfigWriter->save(
                $configPath,
                $element->getData('value'),
                ScopeInterFace::SCOPE_STORES,
                (int)$store->getId()
            );

            $this->logger->debug(
                sprintf('Automatically updated config value for "%s" following feature check', $configPath),
                [
                    'previousValue' => $currentValue,
                    'newValue' => $element->getData('value'),
                    'method' => __METHOD__,
                ]
            );
        } catch (\Exception $e) { // Catching a generic exception to be safe as called from plugin
            $this->logger->error($e->getMessage(), [
                'exception' => get_class($e),
                'configPath' => $configPath,
                'configValue' => $element->getData('value'),
                'method' => __METHOD__,
            ]);
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

    /**
     * @param AbstractElement $element
     * @return string|null
     */
    private function getConfigPath(AbstractElement $element)
    {
        $configPath = $element->getData('field_config/config_path');
        if ($configPath) {
            return (string)$configPath;
        }

        $container = $element->getDataUsingMethod('container');
        if (!$container || !$container->getGroup()) {
            return null;
        }
        $containerHtmlId = $container->getHtmlId();

        $group = $container->getGroup();
        $path = isset($group['path']) ? $group['path'] : '';

        $configPathArray = [];
        $configPathArray['section'] = $path;
        $configPathArray['group'] = str_replace($path . "_", '', $containerHtmlId);
        $configPathArray['field'] = str_replace($containerHtmlId . '_', '', $element->getHtmlId());

        return implode('/', $configPathArray);
    }

    /**
     * @return AccountFeaturesInterface|null
     */
    private function getAccountFeatures()
    {
        if (null === $this->accountFeatures) {
            $storeParam = $this->request->getParam('store');

            $this->accountFeatures = $storeParam
                ? $this->getFeatures->execute($storeParam)
                : null;
        }

        return $this->accountFeatures;
    }
}
