<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration\Instructions;

use Klevu\Search\Helper\VersionReader;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\RepositoryFactory as AssetRepositoryFactory;

class Versions extends Template
{
    const KLEVU_LOGO_PNG = 'Klevu_Search::images/klevu_logo.png';

    /**
     * @var AssetRepositoryFactory
     */
    private $assetRepositoryFactory;
    /**
     * @var VersionReader
     */
    private $versionReader;

    public function __construct(
        Context $context,
        array $data = [],
        AssetRepositoryFactory $assetRepositoryFactory = null,
        VersionReader $versionReader = null
    ) {
        $this->assetRepositoryFactory = $assetRepositoryFactory ?: ObjectManager::getInstance()->get(AssetRepositoryFactory::class);
        $this->versionReader = $versionReader ?: ObjectManager::getInstance()->get(VersionReader::class);

        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getKlevuLogo()
    {
        $imageUrl = '';
        $assetRepository = $this->assetRepositoryFactory->create();
        try {
            $image = $assetRepository->createAsset(
                static::KLEVU_LOGO_PNG,
                ['area' => 'adminhtml']
            );

            $imageUrl = $image->getUrl();
        } catch (LocalizedException $exception) {
            $this->_logger->error('Could not get Klevu Logo: ' . $exception->getMessage());
        }

        return $imageUrl;
    }

    /**
     * @return false|mixed|string
     */
    public function getSearchVersion()
    {
        return $this->versionReader->getVersionString('Klevu_Search');
    }

    /**
     * @return false|mixed|string
     */
    public function getCatNavVersion()
    {
        return $this->versionReader->getVersionString('Klevu_Categorynavigation');
    }

    /**
     * @return false|mixed|string
     */
    public function getRecsVersion()
    {
        return $this->versionReader->getVersionString('Klevu_Recommendations');
    }
}
