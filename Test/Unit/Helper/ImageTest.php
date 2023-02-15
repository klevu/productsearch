<?php
// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Unit\Helper;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Helper\Image as ImageHelper;
use Magento\Backend\Block\Page\RequireJs;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Image;
use Magento\Framework\Image\Adapter\AdapterInterface as ImageAdapterInterface;
use Magento\Framework\Image\Factory as ImageFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    /**
     * @var ImageFactory|(ImageFactory&MockObject)|MockObject
     */
    private $mockImageFactory;
    /**
     * @var Image|(Image&MockObject)|MockObject
     */
    private $mockImage;
    /**
     * @var StoreManagerInterface|(StoreManagerInterface&MockObject)|MockObject
     */
    private $mockStoreManager;
    /**
     * @var ConfigHelper|(ConfigHelper&MockObject)|MockObject
     */
    private $mockConfigHelper;
    /**
     * @var SearchHelper|(SearchHelper&MockObject)|MockObject
     */
    private $mockSearchHelper;
    /**
     * @var RequireJs|(RequireJs&MockObject)|MockObject
     */
    private $mockRequireJs;
    /**
     * @var DirectoryList|(DirectoryList&MockObject)|MockObject
     */
    private $mockDirectoryList;
    /**
     * @var ProductMetadataInterface|(ProductMetadataInterface&MockObject)|MockObject
     */
    private $mockProductMetadata;
    /**
     * @var File|(File&MockObject)|MockObject
     */
    private $mockFileIo;

    public function testThumbImageObject_DoesNotKeepTransparency_ForGifs_WhenProcessorIsGd2()
    {
        $this->setUpPhp5();

        $imageUrl = 'image/url';
        $imageResized = 'image/path.gif';
        $width = 200;
        $height = 200;
        $mimeType = 'image/gif';
        $keepTransparency = false;

        $this->mockConfigHelper->expects($this->once())
            ->method('getImageProcessor')
            ->willReturn(ImageAdapterInterface::ADAPTER_GD2);

        $this->mockImage->expects($this->once())
            ->method('getMimeType')
            ->wilLReturn($mimeType);
        $this->mockImage($imageResized, $width, $height, $keepTransparency);

        $this->mockImageFactory->expects($this->once())
            ->method('create')
            ->with($imageUrl)
            ->willReturn($this->mockImage);

        $imageHelper = $this->instantiateImageHelper();
        $imageHelper->thumbImageObj($imageUrl, $imageResized, $width, $height);
    }

    public function testThumbImageObject_DoesKeepTransparency_WhenProcessorIsNotGd2()
    {
        $this->setUpPhp5();

        $imageUrl = 'image/url';
        $imageResized = 'image/path.gif';
        $width = 200;
        $height = 200;
        $mimeType = 'image/gif';
        $keepTransparency = true;

        $this->mockConfigHelper->expects($this->once())
            ->method('getImageProcessor')
            ->willReturn(ImageAdapterInterface::ADAPTER_IM);

        $this->mockImage->expects($this->never())
            ->method('getMimeType');
        $this->mockImage($imageResized, $width, $height, $keepTransparency);

        $this->mockImageFactory->expects($this->once())
            ->method('create')
            ->with($imageUrl)
            ->willReturn($this->mockImage);

        $imageHelper = $this->instantiateImageHelper();
        $imageHelper->thumbImageObj($imageUrl, $imageResized, $width, $height);
    }

    /**
     * @dataProvider testThumbImageObject_DoesKeepTransparency_ForOtherImageTypes_dataProvider
     */
    public function testThumbImageObject_DoesKeepTransparency_ForOtherImageTypes($mimeType, $imageResized)
    {
        $this->setUpPhp5();

        $imageUrl = 'image/url';
        $width = 200;
        $height = 200;
        $keepTransparency = true;

        $this->mockConfigHelper->expects($this->once())
            ->method('getImageProcessor')
            ->willReturn(ImageAdapterInterface::ADAPTER_GD2);

        $this->mockImage->expects($this->once())
            ->method('getMimeType')
            ->wilLReturn($mimeType);
        $this->mockImage($imageResized, $width, $height, $keepTransparency);

        $this->mockImageFactory->expects($this->once())
            ->method('create')
            ->with($imageUrl)
            ->willReturn($this->mockImage);

        $imageHelper = $this->instantiateImageHelper();
        $imageHelper->thumbImageObj($imageUrl, $imageResized, $width, $height);
    }

    public function testThumbImageObject_DoesKeepTransparency_ForOtherImageTypes_dataProvider()
    {
        return [
            ['image/png', 'image/path.png'],
            ['image/jpg', 'image/path.jpg']
        ];
    }

    /**
     * @dataProvider testGetMediaUrl_dataProvider
     */
    public function testGetMediaUrl($baseUrl, $expected)
    {
        $this->setUpPhp5();

        $secureEnabled = true;
        $storeId = 123;

        $mockStore = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStore->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $mockStore->expects($this->once())
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA, $secureEnabled)
            ->willReturn($baseUrl);

        $this->mockStoreManager->expects($this->once())
            ->method('getStore')
            ->willReturn($mockStore);

        $this->mockConfigHelper->expects($this->once())
            ->method('isSecureUrlEnabled')
            ->with($storeId)
            ->willReturn($secureEnabled);

        $imageHelper = $this->instantiateImageHelper();
        $actual = $imageHelper->getMediaUrl();

        $this->assertSame($expected, $actual);
    }

    public function testGetMediaUrl_dataProvider()
    {
        return [
            ['magento.test/pub/', 'magento.test/needtochange/'],
            ['magento.test/pub/sub_directory/', 'magento.test/needtochange/sub_directory/'],
            ['magento.test/pub/sub_directory/400x300/', 'magento.test/needtochange/sub_directory/400x300/'],
            ['magento.test/media/', 'magento.test/needtochange/media/'],
            ['magento.test/media/sub_directory/', 'magento.test/needtochange/media/sub_directory/'],
            ['magento.test/media/sub_directory/400x300/', 'magento.test/needtochange/media/sub_directory/400x300/'],
            ['magento.test/pub/media/', 'magento.test/needtochange/media/'],
            ['magento.test/pub/media/sub_directory/', 'magento.test/needtochange/media/sub_directory/'],
            ['magento.test/pub/media/sub_directory/400x300/', 'magento.test/needtochange/media/sub_directory/400x300/'],
        ];
    }

    /**
     * @dataProvider testGetImagePath_WhenThumbnailNotCreated_dataProvider
     */
    public function testGetImagePath_WhenThumbnailNotCreated($imagePath, $expected)
    {
        $this->setUpPhp5();

        $storeId = 123;
        $secureEnabled = true;
        $imageWidth = 350;
        $imageHeight = 300;
        $mediaDir = 'media';
        $baseUrl = 'magento.test/';

        $mockStore = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStore->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $mockStore->expects($this->once())
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA, $secureEnabled)
            ->willReturn($baseUrl . $mediaDir . '/');

        $this->mockStoreManager->expects($this->exactly(3))
            ->method('getStore')
            ->willReturn($mockStore);

        $this->mockConfigHelper->expects($this->once())
            ->method('isSecureUrlEnabled')
            ->with($storeId)
            ->willReturn($secureEnabled);
        $this->mockConfigHelper->expects($this->exactly(2))
            ->method('getImageWidth')
            ->with($mockStore)
            ->willReturn($imageWidth);
        $this->mockConfigHelper->expects($this->exactly(2))
            ->method('getImageHeight')
            ->with($mockStore)
            ->willReturn($imageHeight);

        $this->mockDirectoryList->expects($this->once())
            ->method('getPath')
            ->with(DirectoryList::MEDIA)
            ->willReturn($mediaDir);

        $imageHelper = $this->instantiateImageHelper();
        $actual = $imageHelper->getImagePath($imagePath);

        $this->assertSame($expected, $actual);
    }

    public function testGetImagePath_WhenThumbnailNotCreated_dataProvider()
    {
        return [
            ['test.png', 'magento.test/needtochange/media/catalog/product/test.png'],
            ['/test.png', 'magento.test/needtochange/media/catalog/product/test.png'],
            ['dir1/dir2/test.png', 'magento.test/needtochange/media/catalog/product/dir1/dir2/test.png'],
            ['/dir1/dir2/test.png', 'magento.test/needtochange/media/catalog/product/dir1/dir2/test.png']
        ];
    }

    /**
     * @dataProvider testGetImagePath_WhenThumbnailExists_dataProvider
     */
    public function testGetImagePath_WhenThumbnailExists($imagePath, $expected)
    {
        $this->setUpPhp5();

        $storeId = 123;
        $secureEnabled = true;
        $imageWidth = 350;
        $imageHeight = 300;
        $mediaDir = 'media';
        $baseUrl = 'magento.test/';

        $mockStore = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStore->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $mockStore->expects($this->once())
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA, $secureEnabled)
            ->willReturn($baseUrl . $mediaDir . '/');

        $this->mockStoreManager->expects($this->exactly(3))
            ->method('getStore')
            ->willReturn($mockStore);

        $this->mockConfigHelper->expects($this->once())
            ->method('isSecureUrlEnabled')
            ->with($storeId)
            ->willReturn($secureEnabled);
        $this->mockConfigHelper->expects($this->exactly(2))
            ->method('getImageWidth')
            ->with($mockStore)
            ->willReturn($imageWidth);
        $this->mockConfigHelper->expects($this->exactly(2))
            ->method('getImageHeight')
            ->with($mockStore)
            ->willReturn($imageHeight);

        $this->mockDirectoryList->expects($this->once())
            ->method('getPath')
            ->with(DirectoryList::MEDIA)
            ->willReturn($mediaDir);

        $this->mockFileIo->method('fileExists')
            ->willReturn(true);

        $imageHelper = $this->instantiateImageHelper();
        $actual = $imageHelper->getImagePath($imagePath);

        $this->assertSame($expected, $actual);
    }

    public function testGetImagePath_WhenThumbnailExists_dataProvider()
    {
        return [
            ['test.png', 'magento.test/needtochange/media/klevu_images/350X300/test.png'],
            ['/test.png', 'magento.test/needtochange/media/klevu_images/350X300/test.png'],
            ['dir1/dir2/test.png', 'magento.test/needtochange/media/klevu_images/350X300/dir1/dir2/test.png'],
            ['/dir1/dir2/test.png', 'magento.test/needtochange/media/klevu_images/350X300/dir1/dir2/test.png']
        ];
    }

    /**
     * @return void
     */
    private function setUpPhp5()
    {
        $this->mockImageFactory = $this->getMockBuilder(ImageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockImage = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStoreManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockConfigHelper = $this->getMockBuilder(ConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockSearchHelper = $this->getMockBuilder(SearchHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRequireJs = $this->getMockBuilder(RequireJs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDirectoryList = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockProductMetadata = $this->getMockBuilder(ProductMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockFileIo = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ImageHelper
     */
    private function instantiateImageHelper()
    {
        return new ImageHelper(
            $this->mockStoreManager,
            $this->mockConfigHelper,
            $this->mockSearchHelper,
            $this->mockImageFactory,
            $this->mockRequireJs,
            $this->mockDirectoryList,
            $this->mockProductMetadata,
            $this->mockFileIo
        );
    }

    /**
     * @param string $imageResized
     * @param int $width
     * @param int $height
     * @param bool $keepTransparency
     *
     * @return void
     */
    private function mockImage($imageResized, $width, $height, $keepTransparency)
    {
        $this->mockImage->expects($this->once())
            ->method('constrainOnly')
            ->with(true);
        $this->mockImage->expects($this->once())
            ->method('keepAspectRatio')
            ->with(true);
        $this->mockImage->expects($this->once())
            ->method('keepFrame')
            ->with(false);
        $this->mockImage->expects($this->once())
            ->method('backgroundColor')
            ->with([255, 255, 255]);
        $this->mockImage->expects($this->once())
            ->method('keepTransparency')
            ->with($keepTransparency);
        $this->mockImage->expects($this->once())
            ->method('resize')
            ->with($width, $height);
        $this->mockImage->expects($this->once())
            ->method('save')
            ->with($imageResized);
    }
}
