<?php

namespace Klevu\Search\Block\Adminhtml\Form;

use Klevu\Search\Helper\Config as Klevu_HelperConfig;
use Klevu\Search\Model\Order\OrdersWithSameIPCollection;
use Klevu\Search\Model\System\Config\Source\NotificationOptions;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\ModuleList;
use Klevu\Search\Helper\Backend as Klevu_HelperBackend;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Helper\Js as FrameworkJS;

/**
 *
 */
class Information extends Fieldset
{
    const SUBPROCESS_LOCK_FILE = 'klevu_subprocess.lock';
    const AREA_CODE_LOCK_FILE = 'klevu_areacode*.lock';
    protected $_template = 'klevu/search/form/information.phtml';
    protected $_searchHelperConfig;
    protected $_directoryList;
    protected $_moduleList;
    protected $_context;
    /**
     * @var Filesystem
     */
    protected $_fileSystem;
    /**
     * @var Klevu_HelperBackend
     */
    protected $_searchHelperBackend;
    /**
     * @var OrdersWithSameIPCollection|null
     */
    private $ordersWithSameIPCollection;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param FrameworkJS $jsHelper
     * @param Klevu_HelperConfig $config
     * @param Klevu_HelperBackend $searchHelperBackend
     * @param DirectoryList $directoryList
     * @param ModuleList $moduleList
     * @param Filesystem $fileSystem
     * @param OrdersWithSameIPCollection|null $ordersWithSameIPCollection
     * @param array $data
     * @note Unused arguments retained for backwards compatibility
     */
    public function __construct(
        Context                    $context,
        Session                    $authSession,
        FrameworkJS                $jsHelper,
        Klevu_HelperConfig         $config,
        Klevu_HelperBackend        $searchHelperBackend,
        DirectoryList              $directoryList,
        ModuleList                 $moduleList,
        Filesystem                 $fileSystem,
        array                      $data = [],
        OrdersWithSameIPCollection $ordersWithSameIPCollection = null
    )
    {
        $this->_searchHelperConfig = $config;
        $this->_directoryList = $directoryList;
        $this->_moduleList = $moduleList;
        $this->_fileSystem = $fileSystem;
        $this->_searchHelperBackend = $searchHelperBackend;
        $this->_context = $context;
        $this->ordersWithSameIPCollection = $ordersWithSameIPCollection ?: ObjectManager::getInstance()->get(OrdersWithSameIPCollection::class);
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
                $html .= '<div class="message message-error">Magento is not able to execute subprocess command and due to this, data sync has stopped. </br> Klevu has generated Cron Lock file since ' . date("Y-m-d H:i:s", filemtime($value)) . '. To resolve this, Please follow the <a href="https://help.klevu.com/support/solutions/articles/5000871452-setup-external-cron-job" target="_blank">guide</a> to setup external Klevu Cron which is independent of Magento Cron. Once the external Klevu Cron is setup, please <a href=' . $url_lock . '>Click here </a> to remove the Cron lock file.</div>';
            }
        }
        if (!empty($AreaCodefile)) {
            foreach ($AreaCodefile as $key => $value) {
                $params['filename'] = basename($value);
                $url_lock = $this->_context->getUrlBuilder()->getUrl("klevu_search/sync/clearlock", $params);
                $html .= '<div class="message message-error">Klevu Search has detected an <b>Area code is already set</b> error, data sync may not be working correctly. For more information on what this means and how to resolve it, please follow the steps in <a href="https://help.klevu.com/support/solutions/articles/5000871360-area-code-is-already-set" target="_blank">this guide</a>.</div>';
            }
        }

        $flag = $this->_searchHelperBackend->isOutdatedLockFilesExist();
        $option = (int)$this->_searchHelperConfig->getLockFileNotificationOption();
        if ($flag && $option !== NotificationOptions::LOCK_WARNING_DISABLE) {
            $str = __(
                'Klevu Search has detected one or more outdated Lock Files, data sync may not be working correctly.</br>
            Please read about <a href="%1" target="_blank">Magento Lock Files</a> for more information.
            This warning can be disabled via <a href="#row_klevu_search_notification_lock_file">Notification Settings</a>',
                'https://help.klevu.com/support/solutions/articles/5000871506-lock-files-for-data-sync/');
            $html .= '<div class="message message-error">' . $str . '</div>';
        }

        $objVsColFlag = $this->_searchHelperConfig->isCollectionMethodEnabled();
        $option = (int)$this->_searchHelperConfig->getObjMethodNotificationOption();
        if (!$objVsColFlag && $option !== NotificationOptions::LOCK_WARNING_DISABLE) {
            $str = __(
                'Klevu Search is currently using Object method, which may be impacting your data sync performance.</br>
            Please read <a href="%1" target="_blank">Object vs Collection Method</a> for more information.
            This warning can be disabled via <a href="#row_klevu_search_notification_object_vs_collection">Notification Settings</a>.',
                'https://help.klevu.com/support/solutions/articles/5000871455-sync-data-using-collection-method');
            $html .= '<div class="message message-error">' . $str . '</div>';
        }

        /**
         * Warning to show for multiple orders with same IP address
         */
        if (NotificationOptions::LOCK_WARNING_DISABLE !== $this->_searchHelperConfig->isOrdersWithSameIPNotificationOptionEnabled()
            && $this->ordersWithSameIPCollection->execute()
        ) {
            $str = __(
                'Klevu has detected many checkout orders originating from the same IP address causing inaccuracies in Klevu sales analytics.<br />
            Please <a href="%1" target="_blank">read this article</a> for more information on how to resolve this issue.
            This warning can be disabled via <a href="#row_klevu_search_notification_orders_with_same_ip">Notification Settings</a>',
                'https://help.klevu.com/support/solutions/articles/5000874087-multiple-orders-received-from-the-same-ip-address'
            );
            $html .= '<div class="message message-error">' . $str . '</div>';
        }

        $html .= '<div class="kuInfo">';
        $html .= '<div class="message kuInfo-fRight">
      <ul>
         <li><b>Quick Links:</b></li>
         <li><a target="_blank" href="https://help.klevu.com/support/solutions/articles/5000871252-integration-steps-for-magento-2">Integration Steps</a></li>
         <li><a target="_blank" href="https://help.klevu.com/support/solutions/folders/5000308570">Migrating from Staging to Live</a></li>
         <li><a target="_blank" href="https://help.klevu.com/support/solutions/articles/5000871369-how-to-upgrade-my-plan-">How to upgrade plan?</a></li>
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
		  1. Ensure cron is running (<a target="_blank" href="https://help.klevu.com/support/solutions/articles/5000871452-setup-external-cron-job">Click here </a>for more information on setting up a cron )<br>
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
