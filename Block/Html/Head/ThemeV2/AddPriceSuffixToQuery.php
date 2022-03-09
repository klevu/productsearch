<?php

namespace Klevu\Search\Block\Html\Head\ThemeV2;

use Klevu\FrontendJs\Block\Template as FrontendJsTemplate;
use Klevu\FrontendJs\Constants as FrontendJsConstants;

/**
 * @todo Use ViewModels when older Magento BC support dropped
 */
class AddPriceSuffixToQuery extends FrontendJsTemplate
{
    /**
     * @return string
     */
    public function getCustomerDataLoadedEventName()
    {
        return FrontendJsConstants::JS_EVENTNAME_CUSTOMER_DATA_LOADED;
    }

    /**
     * @return string;
     */
    public function getCustomerDataLoadErrorEventName()
    {
        return FrontendJsConstants::JS_EVENTNAME_CUSTOMER_DATA_LOAD_ERROR;
    }

    /**
     * @return bool
     */
    public function shouldOutputLandingScript()
    {
        return (bool)$this->getData('output_landing_script');
    }

    /**
     * @return bool
     */
    public function shouldOutputQuickScript()
    {
        return (bool)$this->getData('output_quick_script');
    }
}
