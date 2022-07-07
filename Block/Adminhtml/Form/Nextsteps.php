<?php

namespace Klevu\Search\Block\Adminhtml\Form;

use Klevu\Search\Api\Service\Account\IntegrationStatusInterface;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Helper\Js as JsHelper;
use Magento\Store\Api\Data\StoreInterface;
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

    public function __construct(
        Context $context,
        AuthSession $authSession,
        JsHelper $jsHelper,
        IntegrationStatusInterface $integrationStatus,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->integrationStatus = $integrationStatus;
        $this->storeManager = $storeManager;
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
            $storeId = null;
        }

        return $this->storeManager->getStore($storeId);
    }
}
