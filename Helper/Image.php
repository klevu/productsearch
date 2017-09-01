<?php

namespace Klevu\Search\Helper;

class Image extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;

    /**
     * @var \Magento\Framework\Image\Factory
     */
    protected $_imageFactory;
    
    /**
     * @var \Magento\Backend\Block\Page\RequireJs
     */
    protected $_requireJs;
    
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
	
	/**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_productMetadataInterface;
	
	
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        \Klevu\Search\Helper\Data $searchHelperData,
        \Magento\Framework\Image\Factory $imageFactory,
        \Magento\Backend\Block\Page\RequireJs $requireJs,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
		\Magento\Framework\App\ProductMetadataInterface $productMetadataInterface

    ) {
    
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_searchHelperData = $searchHelperData;
        $this->_imageFactory = $imageFactory;
        $this->_requireJs = $requireJs;
        $this->_directoryList = $directoryList;
		$this->_productMetadataInterface = $productMetadataInterface;
		if (version_compare($this->_productMetadataInterface->getVersion(), '2.1.0', '>=')===true) {
            // you're on 2.0.13 later version
            $this->_galleryReadHandler = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Catalog\Model\Product\Gallery\ReadHandler');
        } else {
            $this->_galleryReadHandler = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Catalog\Model\Product\Gallery\GalleryManagement');
        }
    }
    
    
    /**
     * Get the secure and unsecure media url
     *
     * @return string
     */
     
    public function getMediaUrl()
    {
        if ($this->_searchHelperConfig->isSecureUrlEnabled($this->_storeModelStoreManagerInterface->getStore()->getId())) {
            $media_url = $this->_storeModelStoreManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true);
        } else {
            $media_url = $this->_storeModelStoreManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        }
        
        $check_root_magento = $this->_requireJs->getViewFileUrl('requirejs/require.js');
        $check_pub = explode('/', $check_root_magento);
        if (!in_array('pub', $check_pub)) {
            $media_url = str_replace('/pub', '/', $media_url);
        }
        
        return $media_url;
    }
    
    /**
     * Decide the image path for search images
     *
     * @param string $image_path (i.e a/b/abc.jpg)
     *
     * @return string
     */
    public function getImagePath($image_path)
    {
        //generate thumbnail image for each products
        $resize_folder = $this->_searchHelperConfig->getImageWidth($this->_storeModelStoreManagerInterface->getStore())."X".$this->_searchHelperConfig->getImageHeight($this->_storeModelStoreManagerInterface->getStore());
        $mediadir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->thumbImage($image_path, $mediadir, $resize_folder);
        $imageResized = $mediadir.DIRECTORY_SEPARATOR."klevu_images/".$resize_folder.$image_path;
        if (file_exists($imageResized)) {
            $product_image_path =  $this->getMediaUrl()."klevu_images/".$resize_folder.$image_path;
        } else {
            $product_image_path = $this->getMediaUrl()."catalog/product". $image_path;
        }
        return $product_image_path;
    }
    
    /**
     * Get the first image from gallery
     *
     * @param object $product
     *
     * @return string
     */
    
    public function getFirstImageFromGallery($product)
    {
        if (version_compare($this->_productMetadataInterface->getVersion(), '2.1.0', '>=')===true) {
            $this->_galleryReadHandler->execute($product);
            $images = $product->getMediaGallery('images');
        } else {
            if (!$this->_searchHelperConfig->isCollectionMethodEnabled()) {
                $m_images = $product->getMediaGalleryEntries();
                if (!empty($m_images)) {
                    foreach ($m_images as $image) {
                        $images[] = $image->getData();
                    }
                }
            }
        }
        if (!empty($images)) {
            $img = 0;
            foreach ($images as $imagkey => $imgvalue) {
                if ($img == 0) {
                    $firstImageOfGallery = $imgvalue['file'];
                    $img++;
                }
            }
            return $firstImageOfGallery;
        }
    }

    /**
     * Generate thumbnail image for each product
     *
     * @param string $image
     *
     * @return $this
     */
        
    public function thumbImage($image, $mediadir, $resize_folder)
    {
        try {
            $baseImageUrl = $mediadir.DIRECTORY_SEPARATOR."catalog".DIRECTORY_SEPARATOR."product".$image;
            if (file_exists($baseImageUrl)) {
                list($width, $height, $type, $attr)=getimagesize($baseImageUrl);
                if ($width > $this->_searchHelperConfig->getImageWidth($this->_storeModelStoreManagerInterface->getStore()) && $height > $this->_searchHelperConfig->getImageHeight($this->_storeModelStoreManagerInterface->getStore())) {
                    $imageResized = $mediadir.DIRECTORY_SEPARATOR."klevu_images/".$resize_folder.$image;
                    if (!file_exists($imageResized)) {
                        $this->thumbImageObj($baseImageUrl, $imageResized);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_searchHelperData->log(\Zend\Log\Logger::DEBUG, sprintf("Image Error:\n%s", $e->getMessage()));
        }
    }
        
    /**
     * Generate 200px thumb image
     *
     * @param string $imageUrl, string $imageResized
     *
     * @return $this
     */
    public function thumbImageObj($imageUrl, $imageResized)
    {
        $imageObj = $this->_imageFactory->create($imageUrl);
        $imageObj->constrainOnly(true);
        $imageObj->keepAspectRatio(true);
        $imageObj->keepFrame(false);
        $imageObj->keepTransparency(true);
        $imageObj->backgroundColor([255, 255, 255]);
        $imageObj->resize($this->_searchHelperConfig->getImageHeight($this->_storeModelStoreManagerInterface->getStore()), $this->_searchHelperConfig->getImageHeight($this->_storeModelStoreManagerInterface->getStore()));
        $imageObj->save($imageResized);
    }
    

    /**
     * get parent product image as prority
     *
     * @param object $parent,object $item,string $attribute
     *
     * @return $string
     */
    
    public function getParentProductImage($parent, $item, $attribute)
    {
        if ($parent && $parent->getData($attribute) && !empty($parent->getData($attribute) && $parent->getData($attribute) != "no_selection")) {
            $product_image = $parent->getData($attribute);
        }
        if ($parent && (empty($parent->getData($attribute)) || $parent->getData($attribute) == "no_selection")) {
            $product_image = $parent->getData('small_image');
            $images = [];
            if (empty($product_image) || $product_image == "no_selection") {
                $product_image = $this->getFirstImageFromGallery($parent);
            }
        }
                                    
        if (empty($product_image) || $product_image == "no_selection") {
            $product_image = $item->getData($attribute);
            if (empty($product_image) || $product_image == "no_selection") {
                $product_image = $item->getData('small_image');
                $images = [];
                if (empty($product_image) || $product_image == "no_selection") {
                     $product_image = $this->getFirstImageFromGallery($item);
                }
            }
        }
        return $product_image;
    }
    
    /**
     * get simple product image as prority
     *
     * @param object $parent,object $item,string $attribute
     *
     * @return $string
     */
    
    public function getSimpleProductImage($parent, $item, $attribute)
    {
        if ($item->getData($attribute) && !empty($item->getData($attribute)) && $item->getData($attribute) != "no_selection") {
            $product_image = $item->getData($attribute);
        }
                                    
        if (empty($product_image) || $product_image == "no_selection") {
            $product_image = $item->getData('small_image');
            $images = [];
            if (empty($product_image) || $product_image == "no_selection") {
                $product_image = $this->getFirstImageFromGallery($item);
            }
                                    
            if ($parent && ($product_image == "no_selection" || empty($product_image))) {
                $product_image = $parent->getData($attribute);
                if (empty($product_image) || $product_image == "no_selection") {
                    $product_image = $parent->getData('small_image');
                    $images = [];
                    if (empty($product_image) || $product_image == "no_selection") {
                        $product_image = $this->getFirstImageFromGallery($item);
                    }
                }
            }
        }
        return $product_image;
    }
}