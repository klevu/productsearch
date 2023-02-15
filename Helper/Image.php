<?php

namespace Klevu\Search\Helper;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Magento\Backend\Block\Page\RequireJs;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Gallery\GalleryManagement;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Framework\Image\Adapter\AdapterInterface as ImageAdapterInterface;
use Magento\Framework\Image\Factory as ImageFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Image extends AbstractHelper
{
    const KLEVU_RESIZED_IMAGES_DIRECTORY = "klevu_images";

    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;
    /**
     * @var SearchHelper
     */
    private $_searchHelperData;
    /**
     * @var ImageFactory
     */
    protected $_imageFactory;
    /**
     * @var RequireJs
     */
    protected $_requireJs;
    /**
     * @var DirectoryList
     */
    protected $_directoryList;
    /**
     * no longer used, maintained for backward compatibility
     * @var ProductMetadataInterface
     */
    protected $_productMetadataInterface;
    /**
     * @var GalleryManagement|ReadHandler|mixed
     */
    protected $_galleryReadHandler;
    /**
     * @var FileIo
     */
    private $fileIo;

    /**
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param Config $searchHelperConfig
     * @param Data $searchHelperData
     * @param ImageFactory $imageFactory
     * @param RequireJs $requireJs
     * @param DirectoryList $directoryList
     * @param ProductMetadataInterface $productMetadataInterface
     * @param FileIo|null $fileIo
     */
    public function __construct(
        StoreManagerInterface $storeModelStoreManagerInterface,
        ConfigHelper $searchHelperConfig,
        SearchHelper $searchHelperData,
        ImageFactory $imageFactory,
        RequireJs $requireJs,
        DirectoryList $directoryList,
        ProductMetadataInterface $productMetadataInterface,
        FileIo $fileIo = null
    ) {
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_searchHelperData = $searchHelperData;
        $this->_imageFactory = $imageFactory;
        $this->_requireJs = $requireJs;
        $this->_directoryList = $directoryList;
        $this->_productMetadataInterface = $productMetadataInterface;
        $objectManager = ObjectManager::getInstance();
        $this->_galleryReadHandler = $objectManager->get(ReadHandler::class);
        $this->fileIo = $fileIo ?: $objectManager->get(FileIo::class);
    }

    /**
     * Get the secure and unsecure media url
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMediaUrl()
    {
        $store = $this->_storeModelStoreManagerInterface->getStore();
        $secureUrlEnabled = $this->_searchHelperConfig->isSecureUrlEnabled($store->getId());
        $mediaUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA, $secureUrlEnabled);
        if (substr($mediaUrl, -1) !== '/') {
            $mediaUrl .= '/';
        }

        $isPubUrl = strpos($mediaUrl, "/pub/") !== false;
        $search = $isPubUrl ? '/pub/' : '/media/';
        $replace = $isPubUrl ? '/needtochange/' : '/needtochange/media/';

        return str_replace($search, $replace, $mediaUrl);
    }

    /**
     * Generates thumbnail images and returns that image URL (not path)
     *
     * @param string $imagePath (i.e a/b/abc.jpg)
     *
     * @return string
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    public function getImagePath($imagePath)
    {
        if (strpos($imagePath, "/") !== 0) { // we are looking for the first character in the string, not false
            $imagePath = '/' . $imagePath;
        }
        //generate thumbnail image for each products
        $store = $this->_storeModelStoreManagerInterface->getStore();
        $resizeFolder = $this->_searchHelperConfig->getImageWidth($store)
            . "X"
            . $this->_searchHelperConfig->getImageHeight($store);

        $mediaDir = $this->_directoryList->getPath(DirectoryList::MEDIA);
        $this->thumbImage($imagePath, $mediaDir, $resizeFolder);

        $imageResized = $mediaDir . DIRECTORY_SEPARATOR . static::KLEVU_RESIZED_IMAGES_DIRECTORY
            . DIRECTORY_SEPARATOR . $resizeFolder . $imagePath;

        $imageUrl = str_replace(DIRECTORY_SEPARATOR, '/', $imagePath);
        $productImageUrl = $this->getMediaUrl();
        if ($this->fileIo->fileExists($imageResized)) {
            $productImageUrl .= static::KLEVU_RESIZED_IMAGES_DIRECTORY . "/" . $resizeFolder . $imageUrl;
        } else {
            $productImageUrl .= "catalog/product" . $imageUrl;
        }

        return $productImageUrl;
    }

    /**
     * Get the first image from gallery
     *
     * @param ProductInterface $product
     *
     * @return string|null
     */
    public function getFirstImageFromGallery($product)
    {
        $this->_galleryReadHandler->execute($product);
        $images = $product->getMediaGallery('images');
        if (!$images) {
            return null;
        }
        $imageValues = array_values($images);

        return isset($imageValues[0]['file']) ? $imageValues[0]['file'] : null;
    }

    /**
     * Generate thumbnail image for each product
     *
     * @param string $image
     * @param string $mediaDir
     * @param string $resizeDir
     *
     * @return void
     */
    public function thumbImage($image, $mediaDir, $resizeDir)
    {
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore();
            $imageWidth = $this->_searchHelperConfig->getImageWidth($store);
            $imageHeight = $this->_searchHelperConfig->getImageHeight($store);
            $baseImagePath = $mediaDir . DIRECTORY_SEPARATOR . "catalog" . DIRECTORY_SEPARATOR . "product" . $image;
            if ($this->fileIo->fileExists($baseImagePath)) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
                list($width, $height, $type, $attr) = getimagesize($baseImagePath);
                if ($width > $imageWidth && $height > $imageHeight) {
                    $imageResized = $mediaDir . DIRECTORY_SEPARATOR . static::KLEVU_RESIZED_IMAGES_DIRECTORY
                        . DIRECTORY_SEPARATOR . $resizeDir . $image;
                    if (!$this->fileIo->fileExists($imageResized)) {
                        $this->thumbImageObj($baseImagePath, $imageResized, $imageWidth, $imageHeight);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_DEBUG,
                sprintf("ImageHelper:: Image Error:\n%s", $e->getMessage())
            );
        }
    }

    /**
     * Generate thumbnail image for Klevu search results
     *
     * @param string $imageUrl
     * @param string $imageResized
     * @param int $width
     * @param int $height
     *
     * @return void
     */
    public function thumbImageObj($imageUrl, $imageResized, $width = 200, $height = 200)
    {
        $imageObj = $this->_imageFactory->create($imageUrl);
        $imageObj->constrainOnly(true);
        $imageObj->keepAspectRatio(true);
        $imageObj->keepFrame(false);
        $keepTransparency = $this->shouldKeepTransparency($imageObj);
        $imageObj->keepTransparency($keepTransparency);
        $imageObj->backgroundColor([255, 255, 255]);
        $imageObj->resize($width, $height);
        $imageObj->save($imageResized);
    }

    /**
     * get parent product image as priority
     *
     * @param ProductInterface $parent
     * @param ProductInterface $item
     * @param string $attribute
     *
     * @return string
     */
    public function getParentProductImage($parent, $item, $attribute)
    {
        if ($parent && $parent->getData($attribute)
            && !empty($parent->getData($attribute)
                && $parent->getData($attribute) !== "no_selection")
        ) {
            $product_image = $parent->getData($attribute);
        }
        if ($parent && (empty($parent->getData($attribute)) || $parent->getData($attribute) === "no_selection")) {
            $product_image = $parent->getData('small_image');
            if (empty($product_image) || $product_image === "no_selection") {
                $product_image = $this->getFirstImageFromGallery($parent);
            }
        }

        if (empty($product_image) || $product_image === "no_selection") {
            $product_image = $item->getData($attribute);
            if (empty($product_image) || $product_image === "no_selection") {
                $product_image = $item->getData('small_image');
                if (empty($product_image) || $product_image === "no_selection") {
                    $product_image = $this->getFirstImageFromGallery($item);
                }
            }
        }

        return $product_image;
    }

    /**
     * get simple product image as prority
     *
     *
     * @param ProductInterface $parent
     * @param ProductInterface $item
     * @param string $attribute
     *
     * @return string
     */
    public function getSimpleProductImage($parent, $item, $attribute)
    {
        if ($item->getData($attribute)
            && !empty($item->getData($attribute))
            && $item->getData($attribute) !== "no_selection"
        ) {
            $product_image = $item->getData($attribute);
        }

        if (empty($product_image) || $product_image === "no_selection") {
            $product_image = $item->getData('small_image');
            if (empty($product_image) || $product_image === "no_selection") {
                $product_image = $this->getFirstImageFromGallery($item);
            }

            if ($parent && ($product_image === "no_selection" || empty($product_image))) {
                $product_image = $parent->getData($attribute);
                if (empty($product_image) || $product_image === "no_selection") {
                    $product_image = $parent->getData('small_image');
                    if (empty($product_image) || $product_image === "no_selection") {
                        $product_image = $this->getFirstImageFromGallery($item);
                    }
                }
            }
        }

        return $product_image;
    }

    /**
     * Generate the image for each product
     *
     * @param string $image_path
     *
     * @return void
     * @throws LocalizedException
     */
    public function generateProductImagesForcefully($image_path)
    {
        $store = $this->_storeModelStoreManagerInterface->getStore();
        $imageWidth = $this->_searchHelperConfig->getImageWidth($store);
        $imageHeight = $this->_searchHelperConfig->getImageHeight($store);
        $resizeDir = $imageWidth . "X" . $imageHeight;
        $mediaDir = $this->_directoryList->getPath(DirectoryList::MEDIA);

        try {
            $baseImagePath = $mediaDir . DIRECTORY_SEPARATOR . "catalog" . DIRECTORY_SEPARATOR
                . "product" . $image_path;
            if ($this->fileIo->fileExists($baseImagePath)) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
                list($width, $height, $type, $attr) = getimagesize($baseImagePath);
                if ($width > $imageWidth && $height > $imageHeight) {
                    $imageResized = $mediaDir . DIRECTORY_SEPARATOR . static::KLEVU_RESIZED_IMAGES_DIRECTORY
                        . DIRECTORY_SEPARATOR . $resizeDir . $image_path;
                    $this->thumbImageObj($baseImagePath, $imageResized, $imageWidth, $imageHeight);
                }
            }
        } catch (\Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_DEBUG,
                sprintf("ImageHelper:: Exception while forcefully regenerating image :%s", $e->getMessage())
            );
        }
    }

    /**
     * @param \Magento\Framework\Image $imageObj
     *
     * @return bool
     */
    private function shouldKeepTransparency(\Magento\Framework\Image $imageObj)
    {
        if ($this->_searchHelperConfig->getImageProcessor() !== ImageAdapterInterface::ADAPTER_GD2) {
            return true;
        }
        $mimeType = $imageObj->getMimeType();

        return strpos($mimeType, 'gif') === false;
    }
}
