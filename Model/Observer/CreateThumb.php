<?php

/**
 * Class \Klevu\Search\Model\Observer
 *
 * @method execute()
 *
 */
namespace Klevu\Search\Model\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Framework\Filesystem as FileSystem;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;

class CreateThumb implements ObserverInterface
{

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_magentoFrameworkFilesystem;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;

    /**
     * @var \Klevu\Search\Helper\Image
     */
    protected $_searchHelperImage;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $_modelProductAction;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    public function __construct(
        DirectoryList $directoryList,
        FileSystem $magentoFrameworkFilesystem,
        Klevu_HelperManager $klevuHelperManager,
        StoreManager $storeModelStoreManager
    ) {

        $this->_directoryList = $directoryList;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_klevuHelperManager = $klevuHelperManager;
        $this->_storeModelStoreManager = $storeModelStoreManager;

    }

    /**
     * When product image updated from admin this will generate the image thumb.
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_searchHelperData =  $this->_klevuHelperManager->getDataHelper();
        $this->_searchHelperImage = $this->_klevuHelperManager->getImageHelper();
        $this->_searchHelperConfig = $this->_klevuHelperManager->getConfigHelper();

        $catalogProduct = $observer->getEvent()->getProduct();
        //Switching to getStoreIds() instead of getStoreId()
        //getStoreId() returns 0 (admin store)
        $storeIds = $observer->getEvent()->getProduct()->getStoreIds();
        if(empty($storeIds)) {
            $this->_searchHelperData->log(\Zend\Log\Logger::DEBUG, sprintf("ProductObserver:: StoreIDs not found for ProductID: %s", $catalogProduct->getId()));
            return ;
        }
        if(!$catalogProduct instanceOf ProductModel) {
            $this->_searchHelperData->log(\Zend\Log\Logger::DEBUG, sprintf("ProductObserver:: Product not found"));
            return ;
        }
        $image = $observer->getEvent()->getProduct()->getImage();

        /* * To do for dynamic image attribute * */

        if (($image != "no_selection") && (!empty($image))) {
            try {
                $pub = $this->_directoryList->getUrlPath("pub");
                $mediadir = $orgMediaDir = $this->_directoryList->getPath(DirectoryList::MEDIA);
                if ($pub == DirectoryList::PUB) {
                    //If pub not being used then will replace /pub/ with / from mediadir
                    //$mediadir = str_replace( '/pub/', '/', $mediadir);
                    $mediadir = str_replace( DirectoryList::PUB,'', $mediadir);
                }

                foreach($storeIds as $storeId) {
                    $image_width = $this->_searchHelperConfig->getImageWidth($storeId);
                    $image_height = $this->_searchHelperConfig->getImageHeight($storeId);

                    $resize_folder = $image_width."X".$image_height;
                    $imageResized = $mediadir.DIRECTORY_SEPARATOR."klevu_images/".$resize_folder.$image;
                    $baseImageUrl = $mediadir.DIRECTORY_SEPARATOR."catalog".DIRECTORY_SEPARATOR."product".$image;

                    $catalogImagePath = $orgMediaDir.DIRECTORY_SEPARATOR."catalog".DIRECTORY_SEPARATOR."product".$image;
                    $klevuImagePath = $orgMediaDir.DIRECTORY_SEPARATOR."klevu_images/".$resize_folder.$image;

                    if (file_exists($catalogImagePath)) {
                        list($width, $height, $type, $attr)= getimagesize($catalogImagePath);
                        if ($width > $image_width && $height > $image_height) {
                            if (file_exists($klevuImagePath)) {
                                if (!unlink($orgMediaDir.'/klevu_images/'.$resize_folder. $image)) {
                                    $this->_searchHelperData->log(\Zend\Log\Logger::DEBUG, sprintf("ProductObserver:: Image Deleting Error:\n%s", $image));
                                }
                            }
                            $this->_searchHelperImage->thumbImageObj($catalogImagePath, $klevuImagePath, $image_width, $image_height);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->_searchHelperData->log(\Zend\Log\Logger::DEBUG, sprintf("Product_Save_After:: Image Error:\n%s", $e->getMessage()));
            }
        }
    }

}
