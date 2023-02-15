<?php
// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Helper;

use Klevu\Search\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    /**
     * @var ProductMetadataInterface|MockObject
     */
    private $mockProductMetadata;
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testGetFirstImageFromGallery()
    {
        $this->setUpPhp5();

        $this->mockProductMetadata->expects($this->exactly(2))
            ->method('getVersion')
            ->willReturn('2.4.3');

        $mockProductBuilder = $this->getMockBuilder(Product::class);
        if (method_exists($mockProductBuilder, 'addMethods')) {
            $mockProductBuilder->addMethods(['getMediaGallery']);
        } else {
            $mockProductBuilder->setMethods(['getMediaGallery']);
        }
        $mockProduct = $mockProductBuilder->disableOriginalConstructor()
            ->getMock();
        $mockProduct->expects($this->once())
            ->method('getMediaGallery')
            ->with('images')
            ->willReturn([
                'first' => ['file' => 'filePath.png'],
                'second' => ['file' => '/dir/filePath.jpg'],
                'third' => ['file' => 'dir/d/e/filePath.gif'],
            ]);

        $mockGalleryReadHandler = $this->getMockBuilder(ReadHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGalleryReadHandler->method('execute');
        $this->objectManager->addSharedInstance($mockGalleryReadHandler, ReadHandler::class);

        $imageHelper = $this->instantiateImageHelper();
        $actual = $imageHelper->getFirstImageFromGallery($mockProduct);

        $expected = 'filePath.png';

        $this->assertSame($expected, $actual);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default_store klevu_search/secureurl_setting/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/secureurl_setting/enabled 0
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_media_url http://www.klevu.com/some-url/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_media_url https://www.klevu.com/some-url/
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetMediaUrl_Unsecure()
    {
        $this->setUpPhp5();

        $this->mockProductMetadata->expects($this->exactly(1))
            ->method('getVersion')
            ->willReturn('2.4.3');

        $store = $this->getStore('klevu_test_store_1');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $imageHelper = $this->instantiateImageHelper();
        $actual = $imageHelper->getMediaUrl();

        $expected = 'http://www.klevu.com/some-url/';

        $this->assertSame($expected, $actual);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default_store klevu_search/secureurl_setting/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/secureurl_setting/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_media_url http://www.klevu.com/media/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_media_url https://www.klevu.com/media/
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetMediaUrl_Secure()
    {
        $this->setUpPhp5();

        $this->mockProductMetadata->expects($this->exactly(1))
            ->method('getVersion')
            ->willReturn('2.4.3');

        $store = $this->getStore('klevu_test_store_1');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $imageHelper = $this->instantiateImageHelper();
        $actual = $imageHelper->getMediaUrl();

        $expected = 'https://www.klevu.com/needtochange/media/';

        $this->assertSame($expected, $actual);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default_store klevu_search/secureurl_setting/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/secureurl_setting/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_media_url http://www.klevu.com/pub
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_media_url https://www.klevu.com/pub
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetMediaUrl_Secure_TrailingNoSlash()
    {
        $this->setUpPhp5();

        $this->mockProductMetadata->expects($this->exactly(1))
            ->method('getVersion')
            ->willReturn('2.4.3');

        $store = $this->getStore('klevu_test_store_1');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $imageHelper = $this->instantiateImageHelper();
        $actual = $imageHelper->getMediaUrl();

        $expected = 'https://www.klevu.com/needtochange/';

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();

        $this->mockProductMetadata = $this->getMockBuilder(ProductMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ImageHelper
     */
    private function instantiateImageHelper()
    {
        return $this->objectManager->create(ImageHelper::class, [
            'productMetadataInterface' => $this->mockProductMetadata,
        ]);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode)
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../_files/websiteFixtures_rollback.php';
    }
}
