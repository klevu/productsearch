<?php

namespace Klevu\Search\Block\Adminhtml\Form;

use Klevu\Search\Helper\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Module\ModuleList;

class Information extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    const SUBPROCESS_LOCK_FILE = 'klevu_subprocess.lock';
    const AREA_CODE_LOCK_FILE = 'klevu_areacode.lock';
    protected $_template = 'klevu/search/form/information.phtml';
    protected $_config;
    protected $_directoryList;
    protected $_moduleList;
    protected $_context;


    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        Config $config,
        DirectoryList $directoryList,
        ModuleList $moduleList,
        array $data = []
    )
    {
        $this->_config = $config;
        $this->_directoryList = $directoryList;
        $this->_moduleList = $moduleList;
        $this->_context = $context;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);
        $html .= $this->_toHtml();
        $html .= '<div class="kuInfo">';
        $html .= '<div class="message kuInfo-fRight">
      <ul>
         <li><b>Quick Links:</b></li>
         <li><a target="_blank" href="https://support.klevu.com/knowledgebase/integration-steps-for-magento-2/">Integration Steps</a></li>
         <li><a target="_blank" href="https://support.klevu.com/section/manuals/magento2-manuals/migrating-from-staging-to-live/">Migrating from Staging to Live</a></li>
         <li><a target="_blank" href="https://support.klevu.com/faq/faqs/how-to-upgrade-my-current-plan/">How to upgrade plan?</a></li>
         <li><a target="_blank" href="https://box.klevu.com/">Klevu Merchant Center</a></li>
      </ul></div>';
        
        $html .= '<div class="kuInfo-fLeft"><p><b>Klevu Search Version</b>: ' . $this->_config->getModuleInfo() . '</p>';
        if (!empty($this->_config->getModuleInfoCatNav())):
            $html .= '<p><b>Klevu Category Navigation Version</b>: ' . $this->_config->getModuleInfoCatNav() . '</p>';
        endif;

                
        $check_plan = $this->_config->getFeatures();
        if (empty($check_plan["errors"]) && !empty($check_plan)) {
            $html .= '<p>';
            if (!empty($check_plan['user_plan_for_store'])) {
                $html .= '<b>My Current Plan: </b>';
                $html .= ucfirst($check_plan['user_plan_for_store']);
            }
            if (!empty($check_plan['upgrade_label'])) {
                $html .= "  <button type='button' onClick=upgradeLink('" . $check_plan["upgrade_url"] . "')>" . $check_plan['upgrade_label'] . "</button>
				 &nbsp;&nbsp;<a href='#' onClick='compareplan();'>Compare Plans</a>";
            }
            $html .= '</p>';
        }
		$html .= '</div>';
        $html .='<div class="kuInfoClear"></div>';
		
        $files = glob($this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR) . '/' . self::SUBPROCESS_LOCK_FILE);
        $AreaCodefile = glob($this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR) . '/' . self::AREA_CODE_LOCK_FILE);
        $messagestr = '';
        if (!empty($files)) {
            foreach ($files as $key => $value) {
                $params['filename'] = basename($value);
                $url_lock = $this->_context->getUrlBuilder()->getUrl("klevu_search/sync/clearlock", $params);
                $html .= "<p><b>Warning:</b><br><p style='color:#FF0000'>Magento is not able to execute subprocess command and due to this, product sync has stopped. Klevu has generated Cron Lock file since " . date("Y-m-d H:i:s", filemtime($value)) . ". To resolve this, Please follow the <a href='https://support.klevu.com/knowledgebase/run-klevu-cron-independent-of-magento-2-cron/' target='_blank'>guide</a> to setup external Klevu Cron which is independent of Magento Cron. Once the external Klevu Cron is setup, please <a href='" . $url_lock . "'>Click here </a> to remove the Cron lock file.</p>";
            }
        }
        if (!empty($AreaCodefile)) {
            foreach ($AreaCodefile as $key => $value) {
                $params['filename'] = basename($value);
                $url_lock = $this->_context->getUrlBuilder()->getUrl("klevu_search/sync/clearlock", $params);
                $html .= "<p><b>Warning:</b><br><p style='color:#FF0000'>We have detected an 'Area code is already set' error so the Klevu product sync has stopped. For more information on what this means and how to resolve it, please follow the steps in <a href='https://support.klevu.com/knowledgebase/resolving-area-code-already-set/' target='_blank'>this guide</a>.</p>";
            }
        }

        $html .= '<p><b>Prerequisites:</b><br>
		  1. Ensure cron is running (<a target="_blank" href="https://support.klevu.com/knowledgebase/setting-up-a-cron-magento-2/">Click here </a>for more information on setting up a cron )<br>
		  2. Indices are uptodate (System &gt; Index Management)<br>
		  3. Products should be enabled and have the visibility set to catalog and search</p>';


        $html .= $this->_getFooterHtml($element);
        return $html;
    }

    public function getFeatures()
    {
        $this->_config->getFeatures();
    }
}
