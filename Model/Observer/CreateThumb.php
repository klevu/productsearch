<?php

namespace Klevu\Search\Model\Observer;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Helper\Image as ImageHelper;
use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\DriverInterface as FilesystemDriverInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

class CreateThumb implements ObserverInterface
{
    /**
     * @var Filesystem
     */
    protected $_magentoFrameworkFilesystem;
    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;
    /**
     * @var ImageHelper
     */
    protected $_searchHelperImage;
    /**
     * @var ProductAction
     */
    protected $_modelProductAction;
    /**
     * @var StoreManager
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var Klevu_HelperManager
     */
    protected $_klevuHelperManager;
    /**
     * @var Klevu Image Helper
     */
    protected $_imageHelper;
    /**
     * @var DirectoryList
     */
    protected $_directoryList;
    /**
     * @var StoreManager
     */
    protected $_storeModelStoreManager;
    /**
     * @var FilesystemDriverInterface
     */
    private $fileDriver;

    /**
     * CreateThumb constructor.
     *
     * @param DirectoryList $directoryList
     * @param Filesystem $magentoFrameworkFilesystem
     * @param Klevu_HelperManager $klevuHelperManager
     * @param StoreManager $storeModelStoreManager
     * @param FilesystemDriverInterface|null $fileDriver
     */
    public function __construct(
        DirectoryList $directoryList,
        Filesystem $magentoFrameworkFilesystem,
        Klevu_HelperManager $klevuHelperManager,
        StoreManager $storeModelStoreManager,
        FilesystemDriverInterface $fileDriver = null
    ) {
        $this->_directoryList = $directoryList;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_klevuHelperManager = $klevuHelperManager;
        $this->_storeModelStoreManager = $storeModelStoreManager;
        $this->fileDriver = $fileDriver ?: ObjectManager::getInstance()->get(FileDriver::class);
    }

    /**
     * When product image updated from admin this will generate the image thumb.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $this->_searchHelperData = $this->_klevuHelperManager->getDataHelper();
        $this->_searchHelperImage = $this->_klevuHelperManager->getImageHelper();
        $this->_searchHelperConfig = $this->_klevuHelperManager->getConfigHelper();

        $catalogProduct = $observer->getEvent()->getProduct();
        //Switching to getStoreIds() instead of getStoreId()
        //getStoreId() returns 0 (admin store)
        $storeIds = $observer->getEvent()->getProduct()->getStoreIds();
        if (empty($storeIds)) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_DEBUG,
                sprintf("ProductObserver:: StoreIDs not found for ProductID: %s", $catalogProduct->getId())
            );

            return;
        }
        if (!$catalogProduct instanceof ProductModel) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_DEBUG,
                "ProductObserver:: Product not found"
            );

            return;
        }
        $image = $observer->getEvent()->getProduct()->getImage();
        /* * To do for dynamic image attribute * */
        if (($image === "no_selection") || (empty($image))) {
            return;
        }
        try {
            $orgMediaDir = $this->_directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR;
            $catalogImagePath = $orgMediaDir . "catalog" . DIRECTORY_SEPARATOR . "product" . $image;
            if (!$this->fileDriver->isExists($catalogImagePath)) {
                return;
            }
            foreach ($storeIds as $storeId) {
                $imageWidth = $this->_searchHelperConfig->getImageWidth($storeId);
                $imageHeight = $this->_searchHelperConfig->getImageHeight($storeId);
                $resizeFolder = $imageWidth . "X" . $imageHeight;
                $klevuImagePath = $orgMediaDir . "klevu_images" . DIRECTORY_SEPARATOR . $resizeFolder . $image;
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                list($width, $height, $type, $attr) = getimagesizefromstring($catalogImagePath);
                if ($width > $imageWidth && $height > $imageHeight) {
                    if ($this->fileDriver->isExists($klevuImagePath)) {
                        try {
                            $this->fileDriver->deleteFile($klevuImagePath);
                        } catch (FileSystemException $e) {
                            $this->_searchHelperData->log(
                                LoggerConstants::ZEND_LOG_DEBUG,
                                sprintf(
                                    'ProductObserver:: Error deleting image [%s] : %s',
                                    $image,
                                    $e->getMessage()
                                )
                            );
                        }
                    }
                    $this->_searchHelperImage->thumbImageObj(
                        $catalogImagePath,
                        $klevuImagePath,
                        $imageWidth,
                        $imageHeight
                    );
                }
            }
        } catch (\Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_DEBUG,
                sprintf("Product_Save_After:: Image Error:\n%s", $e->getMessage())
            );
        }
    }
}
