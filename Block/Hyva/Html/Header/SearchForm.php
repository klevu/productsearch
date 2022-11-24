<?php

namespace Klevu\Search\Block\Hyva\Html\Header;

use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class SearchForm extends Template
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->configHelper = $configHelper;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        if (null === $this->_template) {
            $this->_template = $this->configHelper->isExtensionEnabled()
                ? 'Klevu_Search::hyva/html/header/search-form.phtml'
                : 'Magento_Theme::html/header/search-form.phtml';
        }

        return parent::getTemplate();
    }
}
