<?php

namespace Klevu\Search\Block\Search\Index;

use Klevu\Search\Service\ThemeV2\IsEnabledCondition;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ThemeV2 extends Template
{
    /**
     * @var IsEnabledCondition
     */
    private $isEnabledCondition;

    /**
     * @param Context $context
     * @param IsEnabledCondition $isEnabledCondition
     * @param array $data
     */
    public function __construct(
        Context $context,
        IsEnabledCondition $isEnabledCondition,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->isEnabledCondition = $isEnabledCondition;
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    protected function _toHtml()
    {
        try {
            $store = $this->_storeManager->getStore();
            $storeId = (int)$store->getId();
        } catch (NoSuchEntityException $e) {
            $this->_logger->error($e->getMessage());

            return '';
        }

        if (!$this->isEnabledCondition->execute($storeId)) {
            return '';
        }

        return parent::_toHtml();
    }
}
