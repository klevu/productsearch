<?php

namespace Klevu\Search\Block\Adminhtml\Form;

use Klevu\Search\Helper\Config as Klevu_HelperConfig;
use Klevu\Search\Model\System\Config\Source\NotificationOptions;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\ModuleList;
use Klevu\Search\Helper\Backend as Klevu_HelperBackend;

class Information extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    const SUBPROCESS_LOCK_FILE = 'klevu_subprocess.lock';
    const AREA_CODE_LOCK_FILE = 'klevu_areacode*.lock';
    protected $_template = 'klevu/search/form/information.phtml';
    protected $_searchHelperConfig;
    protected $_directoryList;
    protected $_moduleList;
    protected $_context;


    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        Klevu_HelperConfig $config,
        Klevu_HelperBackend $searchHelperBackend,
        DirectoryList $directoryList,
        ModuleList $moduleList,
        Filesystem $fileSystem,
        array $data = []
    )
    {
        $this->_searchHelperConfig = $config;
        $this->_directoryList = $directoryList;
        $this->_moduleList = $moduleList;
        $this->_fileSystem = $fileSystem;
        $this->_searchHelperBackend = $searchHelperBackend;
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

        $files = glob($this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR) . '/' . self::SUBPROCESS_LOCK_FILE);
        $AreaCodefile = glob($this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR) . '/' . self::AREA_CODE_LOCK_FILE);
        $messagestr = '';
        if (!empty($files)) {
            foreach ($files as $key => $value) {
                $params['filename'] = basename($value);
                $url_lock = $this->_context->getUrlBuilder()->getUrl("klevu_search/sync/clearlock", $params);
                $html .= '<div class="message message-error">Magento is not able to execute subprocess command and due to this, data sync has stopped. </br> Klevu has generated Cron Lock file since ' . date("Y-m-d H:i:s", filemtime($value)) . '. To resolve this, Please follow the <a href="https://support.klevu.com/knowledgebase/run-klevu-cron-independent-of-magento-2-cron/" target="_blank">guide</a> to setup external Klevu Cron which is independent of Magento Cron. Once the external Klevu Cron is setup, please <a href=' . $url_lock . '>Click here </a> to remove the Cron lock file.</div>';
            }
        }
        if (!empty($AreaCodefile)) {
            foreach ($AreaCodefile as $key => $value) {
                $params['filename'] = basename($value);
                $url_lock = $this->_context->getUrlBuilder()->getUrl("klevu_search/sync/clearlock", $params);
                $html .= '<div class="message message-error">Klevu Search has detected an <b>Area code is already set</b> error, data sync may not be working correctly. For more information on what this means and how to resolve it, please follow the steps in <a href="https://support.klevu.com/knowledgebase/resolving-area-code-already-set/" target="_blank">this guide</a>.</div>';
            }
        }

        $flag = $this->_searchHelperBackend->isOutdatedLockFilesExist();
        $option = (int)$this->_searchHelperConfig->getLockFileNotificationOption();
        if ($flag && $option !== NotificationOptions::LOCK_WARNING_DISABLE) {
            $str = __(
                'Klevu Search has detected one or more outdated Lock Files, data sync may not be working correctly.</br>
            Please read about <a href="%1" target="_blank">Magento Lock Files</a> for more information.
            This warning can be disabled via <a href="#row_klevu_search_notification_lock_file">Notification Settings</a>',
                'https://support.klevu.com/knowledgebase/magento-2-lock-files/');
            $html .= '<div class="message message-error">' . $str . '</div>';
        }

        $objVsColFlag = $this->_searchHelperConfig->isCollectionMethodEnabled();
        $option = (int)$this->_searchHelperConfig->getObjMethodNotificationOption();
        if (!$objVsColFlag && $option !== NotificationOptions::LOCK_WARNING_DISABLE) {
            $str = __(
                'Klevu Search is currently using Object method, which may be impacting your data sync performance.</br>
            Please read <a href="%1" target="_blank">Object vs Collection Method</a> for more information.
            This warning can be disabled via <a href="#row_klevu_search_notification_object_vs_collection">Notification Settings</a>.',
                'https://support.klevu.com/knowledgebase/enabling-collection-method-to-sync-data-magento-2');
            $html .= '<div class="message message-error">' . $str . '</div>';
        }

        $html .= '<div class="kuInfo">';
        $html .= '<div class="message kuInfo-fRight">
      <ul>
         <li><b>Quick Links:</b></li>
         <li><a target="_blank" href="https://support.klevu.com/knowledgebase/integration-steps-for-magento-2/">Integration Steps</a></li>
         <li><a target="_blank" href="https://support.klevu.com/section/manuals/magento2-manuals/migrating-from-staging-to-live/">Migrating from Staging to Live</a></li>
         <li><a target="_blank" href="https://support.klevu.com/faq/faqs/how-to-upgrade-my-current-plan/">How to upgrade plan?</a></li>
         <li><a target="_blank" href="https://box.klevu.com/">Klevu Merchant Center</a></li>
      </ul></div>';

        $html .= '<div class="kuInfo-fLeft"><p><b>Klevu Search Version</b>: ' . $this->_searchHelperConfig->getModuleInfo() . '</p>';
        if (!empty($this->_searchHelperConfig->getModuleInfoCatNav())):
            $html .= '<p><b>Klevu Category Navigation Version</b>: ' . $this->_searchHelperConfig->getModuleInfoCatNav() . '</p>';
        endif;

        $check_plan = $this->_searchHelperConfig->getFeatures();
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
        $html .= '<div class="kuInfoClear"></div>';

        $html .= '<p><b>Prerequisites:</b><br>
		  1. Ensure cron is running (<a target="_blank" href="https://support.klevu.com/knowledgebase/setting-up-a-cron-magento-2/">Click here </a>for more information on setting up a cron )<br>
		  2. Indices are uptodate (System &gt; Index Management)<br>
		  3. Products should be enabled and have the visibility set to catalog and search</p>';


        $html .= $this->_getFooterHtml($element);
        return $html;
    }

    public function getFeatures()
    {
        $this->_searchHelperConfig->getFeatures();
    }
}


