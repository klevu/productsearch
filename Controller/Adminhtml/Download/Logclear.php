<?php
/**
 * Class Logclear
 * @package Klevu\Search\Controller\Adminhtml\Download
 */
namespace Klevu\Search\Controller\Adminhtml\Download;

use Klevu\Logger\Api\ArchiveLogFileServiceInterface;
use Klevu\Logger\Api\LogFileNameProviderInterface;
use Klevu\Logger\Api\StoreScopeResolverInterface;
use Klevu\Logger\Controller\Adminhtml\AbstractLogClear;
use Klevu\Search\Helper\Config as Klevu_HelperConfig;
use Klevu\Search\Helper\Data as Klevu_HelperData;
use Magento\Backend\App\Action\Context as ActionContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface as TimezoneInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

class Logclear extends AbstractLogClear
{
    const ADMIN_RESOURCE = Klevu_HelperConfig::ADMIN_RESOURCE_CONFIG;

    /**
     * Construct
     *
     * @param ActionContext $context
     * @param TimezoneInterface $timezone
     * @param DirectoryList $directoryList
     * @param Klevu_HelperData $klevuHelperData
     * @param LoggerInterface|null $logger
     * @param FileIo|null $fileIo
     * @param StoreScopeResolverInterface|null $storeScopeResolver
     * @param LogFileNameProviderInterface|null $logFileNameProvider
     * @param ArchiveLogFileServiceInterface|null $archiveLogFileService
     * @note Unused arguments retained for backwards compatibility
     */
    public function __construct(
        ActionContext $context,
        TimezoneInterface $timezone,
        DirectoryList $directoryList,
        Klevu_HelperData $klevuHelperData,
        LoggerInterface $logger = null,
        FileIo $fileIo = null,
        StoreScopeResolverInterface $storeScopeResolver = null,
        LogFileNameProviderInterface $logFileNameProvider = null,
        ArchiveLogFileServiceInterface $archiveLogFileService = null
    ) {
        parent::__construct(
            $context,
            $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class),
            $directoryList,
            $fileIo ?: ObjectManager::getInstance()->get(FileIo::class),
            $storeScopeResolver ?: ObjectManager::getInstance()->get(StoreScopeResolverInterface::class),
            $logFileNameProvider ?: ObjectManager::getInstance()->get(LogFileNameProviderInterface::class),
            $archiveLogFileService ?: ObjectManager::getInstance()->get(ArchiveLogFileServiceInterface::class)
        );
    }
}
