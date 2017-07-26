<?php

/**
 * Class \Klevu\Search\Model\Observer
 *
 * @method setIsProductSyncScheduled($flag)
 * @method bool getIsProductSyncScheduled()
 */
namespace Klevu\Search\Model\Observer;
 
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout\Interceptor;
use Magento\Framework\Filesystem\DriverPool\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class CreateThumb implements ObserverInterface
{

    /**
     * @var \Klevu\Search\Model\Product\Sync
     */
    protected $_modelProductSync;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_magentoFrameworkFilesystem;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $_modelProductAction;
	
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    public function __construct(
        \Klevu\Search\Model\Product\Sync $modelProductSync,
        \Magento\Framework\Filesystem $magentoFrameworkFilesystem,
        \Klevu\Search\Helper\Data $searchHelperData,
		\Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface
    ) {
    
        $this->_modelProductSync = $modelProductSync;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_searchHelperData = $searchHelperData;
		$this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;

    }
 
    /**
     * When product image updated from admin this will generate the image thumb.
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
		
        $image = $observer->getEvent()->getProduct()->getImage();
        if (($image != "no_selection") && (!empty($image))) {
			
            try {
                $check_root_magento = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Backend\Block\Page\RequireJs')->getViewFileUrl('requirejs/require.js');
                $check_pub = explode('/', $check_root_magento);
                
                $dir = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Filesystem\DirectoryList');
                $mediadir = $dir->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
                if (!in_array('pub', $check_pub)) {
                    $mediadir = str_replace('/pub/', '/', $mediadir);
                }
				
                $config = \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config');
				$image_width = $config->getImageWidth($this->_storeModelStoreManagerInterface->getStore($observer->getEvent()->getProduct()->getStoreId()));
				$image_height = $config->getImageHeight($this->_storeModelStoreManagerInterface->getStore($observer->getEvent()->getProduct()->getStoreId()));
				$resize_folder = $image_width."X".$image_height;
                $imageResized = $mediadir.DIRECTORY_SEPARATOR."klevu_images/".$resize_folder.$image;
                $baseImageUrl = $mediadir.DIRECTORY_SEPARATOR."catalog".DIRECTORY_SEPARATOR."product".$image;

                if (file_exists($baseImageUrl)) {
                    list($width, $height, $type, $attr)= getimagesize($baseImageUrl);
                    if ($width > $image_width && $height > $image_height) {
                        if (file_exists($imageResized)) {
                            if (!unlink($mediadir.'/klevu_images/'.$resize_folder. $image)) {
                                $this->_searchHelperData->log(\Zend\Log\Logger::DEBUG, sprintf("Image Deleting Error:\n%s", $image));
                            }
                        }
                        $this->_modelProductSync->thumbImageObj($baseImageUrl, $imageResized, $resize_folder);
                    }
                }
            } catch (\Exception $e) {
                 $this->_searchHelperData->log(\Zend\Log\Logger::DEBUG, sprintf("Image Error:\n%s", $e->getMessage()));
            }
        }
    }
}
