<?php

namespace Klevu\Search\Controller\Adminhtml\Download;

use Klevu\Logger\Api\LogFileNameProviderInterface;
use Klevu\Logger\Api\StoreScopeResolverInterface;
use Klevu\Logger\Controller\Adminhtml\AbstractLogDownload;
use Klevu\Logger\Validator\ArgumentValidationTrait;
use Klevu\Search\Helper\Data as Klevu_HelperData;
use Magento\Backend\App\Action\Context as ActionContext;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Archive\Zip;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface as TimezoneInterface;
use Psr\Log\LoggerInterface;

class Logdownload extends AbstractLogDownload
{
    use ArgumentValidationTrait;

    /**
     * Logdownload constructor.
     * @param ActionContext $context
     * @param TimezoneInterface $timezone
     * @param DirectoryList $directoryList
     * @param Zip $zip
     * @param FileFactory $fileFactory
     * @param Klevu_HelperData $klevuHelperData
     * @param LoggerInterface|null $logger
     * @param FileIo|null $fileIo
     * @param StoreScopeResolverInterface|null $storeScopeResolver
     * @param LogFileNameProviderInterface|null $logFileNameProvider
     * @param int|null $maxFileSize
     */
    public function __construct(
        ActionContext $context,
        TimezoneInterface $timezone,
        DirectoryList $directoryList,
        Zip $zip,
        FileFactory $fileFactory,
        Klevu_HelperData $klevuHelperData,
        LoggerInterface $logger = null,
        FileIo $fileIo = null,
        StoreScopeResolverInterface $storeScopeResolver = null,
        LogFileNameProviderInterface $logFileNameProvider = null,
        $maxFileSize = null
    ) {
        parent::__construct(
            $context,
            $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class),
            $fileIo ?: ObjectManager::getInstance()->get(FileIo::class),
            $directoryList,
            $storeScopeResolver ?: ObjectManager::getInstance()->get(StoreScopeResolverInterface::class),
            $logFileNameProvider ?: ObjectManager::getInstance()->get(LogFileNameProviderInterface::class),
            $zip,
            $fileFactory,
            $maxFileSize
        );
    }
}
