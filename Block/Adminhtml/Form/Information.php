<?php

namespace Klevu\Search\Block\Adminhtml\Form;

class Information extends \Magento\Config\Block\System\Config\Form\Fieldset
{

    protected $_template = 'klevu/search/form/information.phtml';

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);
        $html .= $this->_toHtml();
		$html .='<p><b>Installed version</b>: '.$this->getModuleInfo().'</p>';
        $heperData = \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config');
        $check_plan = $heperData->getFeatures();
        if (empty($check_plan["errors"]) && !empty($check_plan)) {
            $html .='<p>';
            if (!empty($check_plan['user_plan_for_store'])) {
                    $html .= '<b>My Current Plan: </b>';
                    $html .= ucfirst($check_plan['user_plan_for_store']);
            }
            if (!empty($check_plan['upgrade_label'])) {
                 $html .= "  <button type='button' onClick=upgradeLink('".$check_plan["upgrade_url"]."')>".$check_plan['upgrade_label']."</button>
				 &nbsp;&nbsp;<a href='#' onClick='compareplan();'>Compare Plans</a>";
            }
            $html .= '</p>';
        }
		
		$dir = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Filesystem\DirectoryList');
		$files = glob($dir->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR).'/subprocess.lock');
		$UrlInterface = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\UrlInterface');
		$messagestr = '';
		if(!empty($files)) {
			foreach($files as $key => $value) {
				$params['filename'] = basename($value);
				$url_lock = $UrlInterface->getUrl("klevu_search/sync/clearlock",$params);
				$html.=  "<p><b>Warning:</b><br><p style='color:#FF0000'>Magento is not able to execute subprocess command and due to this, product sync has stopped. Klevu has generated Cron Lock file since ".date ("Y-m-d H:i:s", filemtime($value)) .". To resolve this, Please follow the <a href='https://support.klevu.com/knowledgebase/run-klevu-cron-independent-of-magento-2-cron/' target='_blank'>guide</a> to setup external Klevu Cron which is independent of Magento Cron. Once the external Klevu Cron is setup, please <a href='" . $url_lock . "'>Click here </a> to remove the Cron lock file.</p>";
			}
		}  
		
        $html .= '<p><b>Prerequisites:</b><br>
		  1. Ensure cron is running <br>
		  2. Indices are uptodate (System &gt; Index Management)<br>
		  3. Products should be enabled and have the visibility set to catalog and search</p>';
		  
		
        $html .= $this->_getFooterHtml($element);
        return $html;
    }
	
	public function getModuleInfo()
    {
        $moduleInfo = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Module\ModuleList')->getOne('Klevu_Search');
        return $moduleInfo['setup_version'];
    }
}
