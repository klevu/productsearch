<?php

namespace Klevu\Search\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context as Template_Context;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;

class Field extends \Magento\Config\Block\System\Config\Form\Field
{

    private $_context;

    private $storeManager;

    private $_klevuHelperManager;

    public function __construct(
        Template_Context $context,
        Klevu_HelperManager $klevuHelperManager,
        array $data = []

    ) {
        $this->_klevuHelperManager = $klevuHelperManager;
        parent::__construct($context,$data);
        $this->_context = $context;

    }

    /**
     * Retrieve element HTML markup
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $element->getElementHtml();
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $isCheckboxRequired = $this->_isInheritCheckboxRequired($element);

        $fieldToHide = array(
            'klevu_search_add_to_cart_enabledaddtocartfront','klevu_search_attributes_other',
            'klevu_search_attributes_boosting','klevu_search_cmscontent_enabledcmsfront',
            'klevu_search_cmscontent_excludecms_pages','klevu_search_secureurl_setting_enabled',
            'klevu_search_image_setting_enabled','klevu_search_image_setting_image_width',
            'klevu_search_image_setting_image_height','klevu_search_general_category_navigation_url',
            'klevu_search_categorylanding_max_no_of_products',
            'klevu_search_categorylanding_klevu_cat_relevance','klevu_search_searchlanding_klevu_search_relevance');

        $this->storeManager = $this->_context->getStoreManager();
        $store_mode = $this->storeManager->isSingleStoreMode();

        if( in_array( $element->getId(), $fieldToHide ) ) {
            //Removing checkbox when SSM is off and view is Global or Websites
            if(!$store_mode && $element->getScope() != "stores" )  {
                $element->unsetData(null);
                return;
            }elseif (!$store_mode && ( $element->getScope() == "stores" || $element->getScope() == "websites") ){
                $isCheckboxRequired = false;
            }
        }

        // Disable element if value is inherited from other scope. Flag has to be set before the value is rendered.
        if ($element->getInherit() == 1 && $isCheckboxRequired) {
            $element->setDisabled(true);
        }


        $klevuConfig = $this->_klevuHelperManager->getConfigHelper();
        $features = $klevuConfig->getFeaturesUpdate($element->getHtmlId());

        if($store_mode) {
            $labelsToHide = array('klevu_search_add_to_cart_enabled_info','klevu_search_attributes_info_attribute','klevu_search_cmscontent_enabledcmsfront_info','klevu_search_secureurl_setting_info_enabled','klevu_search_image_setting_info_enabled');
            if(in_array($element->getId(),$labelsToHide) && $element->getType()=='label') {
                $element->unsetData(null);
            }


            $jsApiValue = $klevuConfig->getJsApiKey($this->storeManager->getStore());
            $jsRestValue = $klevuConfig->getRestApiKey($this->storeManager->getStore());
            $cloudSearchURL = $klevuConfig->getCloudSearchUrl($this->storeManager->getStore());

            $klevuConfig->setStoreConfig('klevu_search/general/js_api_key', $jsApiValue,$this->storeManager->getStore());
            $klevuConfig->setStoreConfig('klevu_search/general/rest_api_key', $jsRestValue,$this->storeManager->getStore());

        }
        if (!empty($features)) {
            $style        = 'class="klevu-disabled"';
            $upgrade_text = '';
            if (!empty($features['upgrade_message']) || !empty($features['upgrade_label'])) {
                $upgrade_text .= "<div class='klevu-upgrade-block'>";
                if (!empty($features['upgrade_message'])) {
                    $upgrade_text .= $features['upgrade_message'];
                }
                if (!empty($features['upgrade_label'])) {
                    $upgrade_text .= "<br/><button type='button' onClick=upgradeLink('" . $features["upgrade_url"] . "')>" . $features['upgrade_label'] . "</button>";
                }
                $upgrade_text .= "</div>";
            }
        } else {
            $style        = '';
            $upgrade_text = '';
        }

        // Code added by klevu
        if (!empty($features)) {
            $element->setDisabled(true);
            $element->setValue(0);
        }

        $html = '<td class="label"><label for="' .
            $element->getHtmlId() .
            '">' .
            $element->getLabel() .$upgrade_text.
            '</label></td>';
        $html .= $this->_renderValue($element);

        if ($isCheckboxRequired) {
            $html .= $this->_renderInheritCheckbox($element);
        }

        $html .= $this->_renderScopeLabel($element);
        $html .= $this->_renderHint($element);

        return $this->_decorateRowHtml($element, $html);
    }

    /**
     * Render element value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderValue(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {

        if ($element->getTooltip()) {
            $html = '<td class="value with-tooltip">';
            $html .= $this->_getElementHtml($element);
            $html .= '<div class="tooltip"><span class="help"><span></span></span>';
            $html .= '<div class="tooltip-content">' . $element->getTooltip() . '</div></div>';
        } else {
            $html = '<td class="value">';
            $html .= $this->_getElementHtml($element);
        }
        if ($element->getHtmlId() == "klevu_search_searchlanding_landenabled") {
            $klevu_html ='';
            $klevuConfig = $this->_klevuHelperManager->getConfigHelper();
            $check_preserve = $klevuConfig->getFeatures();
            if (!empty($check_preserve)) {
                if (isset($check_preserve['disabled']) && !empty($check_preserve['disabled'])) {
                    if (strpos($check_preserve['disabled'], "preserves_layout") !== false) {
                        $klevu_html ="";
                        if (!empty($check_preserve['preserve_layout_message']) || !empty($check_preserve['upgrade_label'])) {
                            $klevu_html.=  "<div class='klevu-upgrade-block-simple'>";
                            if (!empty($check_preserve['preserve_layout_message'])) {
                                $klevu_html.=$check_preserve['preserve_layout_message'];
                            }

                            $klevu_html.="</div>";
                        }
                    }
                }
            }

            if ($klevu_html) {
                $html .= '<p class="note"><span>' . $klevu_html . '</span></p>';
            } elseif ($element->getComment()) {
                $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
            }
        } else {
            if ($element->getComment()) {
                $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
            }
        }
        $html .= '</td>';
        return $html;
    }

    /**
     * Render inheritance checkbox (Use Default or Use Website)
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderInheritCheckbox(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $htmlId = $element->getHtmlId();
        $namePrefix = preg_replace('#\[value\](\[\])?$#', '', $element->getName());
        $checkedHtml = $element->getInherit() == 1 ? 'checked="checked"' : '';

        $html = '<td class="use-default">';
        $html .= '<input id="' .
            $htmlId .
            '_inherit" name="' .
            $namePrefix .
            '[inherit]" type="checkbox" value="1"' .
            ' class="checkbox config-inherit" ' .
            $checkedHtml .
            ' onclick="toggleValueElements(this, Element.previous(this.parentNode))" /> ';
        $html .= '<label for="' . $htmlId . '_inherit" class="inherit">' . $this->_getInheritCheckboxLabel(
                $element
            ) . '</label>';
        $html .= '</td>';

        return $html;
    }

    /**
     * Check if inheritance checkbox has to be rendered
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return bool
     */
    protected function _isInheritCheckboxRequired(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $element->getCanUseWebsiteValue() || $element->getCanUseDefaultValue();
    }

    /**
     * Retrieve label for the inheritance checkbox
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getInheritCheckboxLabel(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $checkboxLabel = __('Use Default');
        if ($element->getCanUseWebsiteValue()) {
            $checkboxLabel = __('Use Website');
        }
        return $checkboxLabel;
    }

    /**
     * Render scope label
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderScopeLabel(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '<td class="scope-label">';
        if ($element->getScope() && false == $this->_storeManager->isSingleStoreMode()) {
            $html .= $element->getScopeLabel();
        }
        $html .= '</td>';
        return $html;
    }

    /**
     * Render field hint
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderHint(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '<td class="">';
        if ($element->getHint()) {
            $html .= '<div class="hint"><div style="display: none;">' . $element->getHint() . '</div></div>';
        }
        $html .= '</td>';
        return $html;
    }
}
