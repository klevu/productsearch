<?php

namespace Klevu\Search\Block\Adminhtml\Form;

use Klevu\Registry\Api\ConfigRegistryInterface;
use Klevu\Search\Api\Service\Account\IntegrationStatusInterface;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Helper\Js as JsHelper;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class Nextsteps extends Fieldset
{
    /**
     * @var IntegrationStatusInterface
     */
    private $integrationStatus;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ConfigRegistryInterface
     */
    private $configRegistry;

    /**
     * @param Context $context
     * @param AuthSession $authSession
     * @param JsHelper $jsHelper
     * @param IntegrationStatusInterface $integrationStatus
     * @param StoreManagerInterface $storeManager
     * @param array $data
     * @param ConfigRegistryInterface|null $configRegistry
     */
    public function __construct(
        Context $context,
        AuthSession $authSession,
        JsHelper $jsHelper,
        IntegrationStatusInterface $integrationStatus,
        StoreManagerInterface $storeManager,
        array $data = [],
        ?ConfigRegistryInterface $configRegistry = null
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->integrationStatus = $integrationStatus;
        $this->storeManager = $storeManager;
        $this->configRegistry = $configRegistry ?: ObjectManager::getInstance()->get(ConfigRegistryInterface::class);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if (!$this->integrationStatus->isJustIntegrated()) {
            return '';
        }
        try {
            $store = $this->getStore();
            $this->integrationStatus->setIntegrated($store);
        } catch (NoSuchEntityException $exception) {
            $this->_logger->error($exception->getMessage());
        }

        return parent::render($element);
    }

    /**
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore()
    {
        $storeId = $this->_request->getParam('store');
        if ('' === (string)$storeId) {
            $storeId = $this->configRegistry->isSingleStoreMode() ? Store::DEFAULT_STORE_ID : null;
        }

        return $this->storeManager->getStore($storeId);
    }
}
