<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field;

use Klevu\Logger\Api\KlevuLoggerInterface;
use Klevu\Logger\Api\LogFileNameProviderInterface;
use Klevu\Logger\Block\Adminhtml\Form\Button\LogDownloadButton;
use Klevu\Search\Helper\Data as Klevu_HelperData;
use Magento\Backend\Block\Template\Context as Template_Context;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @deprecated See \Klevu\Logger\Block\Adminhtml\Form\Button\LogDownloadButton
 */
class Logdownload extends LogDownloadButton
{
    /**
     * Logdownload constructor.
     * @param Template_Context $context
     * @param DirectoryList $directoryList
     * @param Klevu_HelperData $klevuHelperData
     * @param array $data
     * @param FileIo|null $fileIo
     * @param StoreManagerInterface|null $storeManager
     * @param KlevuLoggerInterface|null $logger
     * @param LogFileNameProviderInterface|null $logFileNameProvider
     * @param string $destinationUrl
     * @param string $buttonLabel
     * @note Unused arguments retained for backwards compatibility
     */
    public function __construct(
        Template_Context $context,
        DirectoryList $directoryList,
        Klevu_HelperData $klevuHelperData,
        array $data = [],
        FileIo $fileIo = null,
        StoreManagerInterface $storeManager = null,
        KlevuLoggerInterface $logger = null,
        LogFileNameProviderInterface $logFileNameProvider = null,
        $destinationUrl = 'klevu_search/download/logdownload',
        $buttonLabel = 'Download Klevu Search Log'
    ) {
        parent::__construct(
            $context,
            $logger ?: ObjectManager::getInstance()->get(KlevuLoggerInterface::class),
            $directoryList,
            $fileIo ?: ObjectManager::getInstance()->get(FileIo::class),
            $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class),
            $logFileNameProvider ?: ObjectManager::getInstance()->get(LogFileNameProviderInterface::class),
            $destinationUrl,
            $buttonLabel,
            $data
        );
    }
}
