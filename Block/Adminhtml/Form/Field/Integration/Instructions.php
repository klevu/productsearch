<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration;

use Klevu\Search\Api\Service\Account\IntegrationStatusInterface;
use Klevu\Search\Block\Adminhtml\Form\Field\Integration\Instructions\Integration;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Helper\Js as JsHelper;

class Instructions extends Fieldset
{
    /**
     * @var IntegrationStatusInterface
     */
    private $integrationStatus;

    public function __construct(
        Context $context,
        Session $authSession,
        JsHelper $jsHelper,
        IntegrationStatusInterface $integrationStatus,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->integrationStatus = $integrationStatus;
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     * @throws LocalizedException
     */
    public function render(AbstractElement $element)
    {
        $store = $this->_request->getParam('store');
        if (!$store) {
            return '';
        }
        if ($this->integrationStatus->isIntegrated()) {
            return '';
        }
        $layout = $this->getLayout();
        $block = $layout->createBlock(
            Integration::class,
            'klevu_search_information_api_key_integration_instructions'
        );
        $block->setTemplate(
            'Klevu_Search::klevu/search/form/field/integration/instructions.phtml'
        );

        return $block->toHtml();
    }
}
