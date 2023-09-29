<?php

namespace Klevu\Search\Console\Command;

use Klevu\Logger\Api\StoreScopeResolverInterface;
use Klevu\Search\Model\Product\MagentoProductActionsInterface as MagentoProductActions;
use Klevu\Search\Model\Product\KlevuProductActionsInterface as KlevuProductActions;
use Klevu\Search\Model\Product\Sync as Sync;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\DriverInterface as FilesystemDriverInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Psr\Log\LoggerInterface as LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCategoryCommand extends Command
{
    const LOCK_FILE = 'klcatentity_klevu_running_index.lock';
    const AREA_CODE_LOCK_FILE = 'klevu_catentity_areacode.lock';

    const ALLDATA_CATEGORY_DESC = 'Send all Category records to Klevu.';
    const UPDATESONLY_CATEGORY_DESC = 'Only send those Category records which have been modified since the last sync with Klevu.'; // phpcs:ignore Generic.Files.LineLength.TooLong

    /**
     * @var State
     */
    protected $state;

    /**
     * @var StoreManagerInterface
     */
    protected $storeInterface;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var Sync
     */
    protected $sync;

    /**
     * @var null
     */
    protected $magentoProductActions;

    /**
     * @var StoreScopeResolverInterface
     */
    private $storeScopeResolver;

    /**
     * @var string|null
     */
    private $klevuLoggerFQCN;

    /**
     * @var KlevuProductActions
     */
    protected $klevuProductActions;

    /**
     * @var FilesystemDriverInterface
     */
    private $fileDriver;

    /**
     * @var string[][]
     */
    protected $websiteList = [];

    /**
     * @param State $state
     * @param StoreManagerInterface $storeInterface
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param StoreScopeResolverInterface|null $storeScopeResolver
     * @param string|null $klevuLoggerFQCN
     * @param FilesystemDriverInterface|null $fileDriver
     */
    public function __construct(
        State $state,
        StoreManagerInterface $storeInterface,
        DirectoryList $directoryList,
        LoggerInterface $logger,
        StoreScopeResolverInterface $storeScopeResolver = null,
        $klevuLoggerFQCN = null,
        FilesystemDriverInterface $fileDriver = null
    ) {
        $this->state = $state;
        $this->storeInterface = $storeInterface;
        $this->directoryList = $directoryList;
        $this->_logger = $logger;
        $this->storeScopeResolver = $storeScopeResolver;
        if (is_string($klevuLoggerFQCN)) {
            $this->klevuLoggerFQCN = $klevuLoggerFQCN;
        }
        $this->fileDriver = $fileDriver ?: ObjectManager::getInstance()->get(FileDriver::class);

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('klevu:sync:category')
            ->setDescription(
                'Sync Category data with Klevu for all stores.' . PHP_EOL
                . 'You can specify whether to process all categories or just those that have changed via an '
                . 'option detailed below.' . PHP_EOL
                . 'If no option is specified, --updatesonly will be used.'
            )->setDefinition($this->getInputList())
            ->setHelp(
                <<<HELP

Only send categories which have been modified since the last sync with Klevu:
    <comment>%command.full_name% --updatesonly</comment>

Send all categories to Klevu:
    <comment>%command.full_name% --alldata</comment>

HELP
            );
        parent::configure();
    }

    /**
     * CLI command description
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // See comments against methods for background. Ref: KS-7853
        $this->initLogger();
        $this->initStoreScopeResolver();

        $this->storeScopeResolver->setCurrentStoreById(0);
        $logDir = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $storeLockFile = '';
        $areaCodeFile = $logDir . "/" . self::AREA_CODE_LOCK_FILE;
        try {
            if ($this->fileDriver->isExists($areaCodeFile)) {
                $this->fileDriver->deleteFile($areaCodeFile);
            }
            $this->state->setAreaCode(Area::AREA_FRONTEND);
        } catch (LocalizedException $e) {
            $this->fileDriver->fileOpen($areaCodeFile, 'w');
            if ($this->state->getAreaCode() != Area::AREA_FRONTEND) {
                $output->writeln(__(
                    sprintf('Category sync running in an unexpected state AreaCode : (%s)', $this->state->getAreaCode())
                )->getText());
            }
        }

        $storeList = $this->storeInterface->getStores();
        $syncFailed = $syncSuccess = [];
        $storeCodesAll = [];
        foreach ($storeList as $store) {
            $storeWebsiteId = $store->getWebsiteId();
            if (!isset($this->websiteList[$storeWebsiteId])) {
                $this->websiteList[$storeWebsiteId] = [];
            }
            if (!in_array($store->getCode(), $this->websiteList[$storeWebsiteId], true)) {
                $this->websiteList[$storeWebsiteId][] = $store->getCode();
            }
            $storeCodesAll[] = $store->getCode();
        }

        $this->sync = ObjectManager::getInstance()->get(Sync::class);
        $this->magentoProductActions = ObjectManager::getInstance()->get(MagentoProductActions::class);
        $this->klevuProductActions = ObjectManager::getInstance()->get(KlevuProductActions::class);

        try {
            $output->writeln("=== Starting storewise Category data sync ===");
            $output->writeln('');

            if ($input->hasParameterOption('--alldata')) {
                $output->writeln('<info>Category Synchronization started using --alldata option.</info>');
                $this->magentoProductActions->markCategoryRecordIntoQueue();
            } elseif ($input->hasParameterOption('--updatesonly')) {
                $output->writeln('<info>Category Synchronization started using --updatesonly option.</info>');
            } else {
                $output->writeln(
                    '<info>No option provided. Category Synchronization started using updatesonly option.</info>'
                );
            }

            if (count($storeCodesAll) > 0) {
                foreach ($storeCodesAll as $rowStoreCode) {
                    $this->storeScopeResolver->setCurrentStoreByCode($rowStoreCode);

                    $storeLockFile = $logDir . "/" . $rowStoreCode . "_" . self::LOCK_FILE;
                    if ($this->fileDriver->isExists($storeLockFile)) {
                        $output->writeln(
                            sprintf(
                                '<error>Klevu Category sync process cannot start because a lock file exists for '
                                . 'store code: %s, skipping this store.</error>',
                                $rowStoreCode
                            )
                        );
                        $output->writeln("");
                        $syncFailed[] = $rowStoreCode;
                        continue;
                    }
                    $this->fileDriver->fileOpen($storeLockFile, 'w');
                    $rowStoreObject = $this->storeInterface->getStore($rowStoreCode);
                    if (!is_object($rowStoreObject)) {
                        $output->writeln(
                            sprintf(
                                '<error>Store object found invalid for store code : %s, skipping this store.</error>',
                                $rowStoreCode
                            )
                        );
                        $output->writeln("");
                        $syncFailed[] = $rowStoreCode;
                        continue;
                    }

                    if (!$this->klevuProductActions->setupSession($rowStoreObject)) {
                        if ($this->fileDriver->isExists($storeLockFile)) {
                            $this->fileDriver->deleteFile($storeLockFile);
                        }
                        continue;
                    }

                    $output->writeln('');
                    $output->writeln(
                        sprintf(
                            '<info>Category Sync started for store code : %s</info>',
                            $rowStoreObject->getCode()
                        )
                    );
                    $msg = $this->sync->runCategory($rowStoreObject);
                    if (!empty($msg)) {
                        $output->writeln("<comment>" . $msg . "</comment>");
                    }
                    $output->writeln(
                        sprintf(
                            '<info>Category Sync completed for store code : %s</info>',
                            $rowStoreObject->getCode()
                        )
                    );

                    $syncSuccess[] = $rowStoreObject->getCode();

                    if ($this->fileDriver->isExists($storeLockFile)) {
                        $this->fileDriver->deleteFile($storeLockFile);
                    }
                    $output->writeln("<info>********************************</info>");
                }
                $this->storeScopeResolver->setCurrentStoreById(0);
            }

        } catch (\Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>Error thrown in store wise Category data sync: %s</error>',
                    $e->getMessage()
                )
            );
            if (isset($storeLockFile)) {
                if ($this->fileDriver->isExists($storeLockFile)) {
                    $this->fileDriver->deleteFile($storeLockFile);
                }
            }
            $this->storeScopeResolver->setCurrentStoreById(0);

            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
        $output->writeln('');
        if (!empty($syncSuccess)) {
            $output->writeln(
                sprintf(
                    '<info>Category Sync successfully completed for store code(s): %s</info>',
                    implode(',', $syncSuccess)
                )
            );
        }
        if (!empty($syncFailed)) {
            $output->writeln(
                sprintf(
                    '<error>Category Sync did not complete for store code(s): %s</error>',
                    implode(',', $syncFailed)
                )
            );
        }

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    /**
     * @return InputOption[]
     */
    public function getInputList()
    {
        $inputList = [];

        $inputList[] = new InputOption(
            'updatesonly',
            null,
            InputOption::VALUE_OPTIONAL,
            self::UPDATESONLY_CATEGORY_DESC
        );

        $inputList[] = new InputOption(
            'alldata',
            null,
            InputOption::VALUE_OPTIONAL,
            self::ALLDATA_CATEGORY_DESC
        );

        return $inputList;
    }

    /**
     * Check that the logger property is of the expected class and, if not, create using OM
     *
     * In order to support updates from 2.3.x to 2.4.x, which introduced the logger module,
     *  we can't inject the actual logger through DI as all CLI commands are instantiated
     *  by bin/magento. This prevents setup:upgrade running and enabling the logger module
     *  because the logger module isn't already enabled.
     * As such, we pass an FQCN for the desired logger class and then check that it matches
     *  at the start of any method utilising it
     * We avoid temporal coupling by falling back to the standard LoggerInterface in the
     *  constructor
     *
     * @return void
     */
    private function initLogger()
    {
        if (!($this->_logger instanceof LoggerInterface)) {
            $objectManager = ObjectManager::getInstance();
            if ($this->klevuLoggerFQCN && !($this->_logger instanceof $this->klevuLoggerFQCN)) {
                $this->_logger = $objectManager->get($this->klevuLoggerFQCN);
            } elseif (!$this->_logger) {
                $this->_logger = $objectManager->get(LoggerInterface::class);
            }
        }
    }

    /**
     * Instantiate the StoreScopeResolver property
     *
     * For the same reasons as initLogger is required, we can't inject a class from a new
     *  module into a CLI command. Unlike initLogger, however, this is a new property so
     *  the usual $this->>storeScopeResolver
     *      = $storeScopeResolver ?: ObjectManager::getInstance()->get(StoreScopeResolverInterface::class)
     *  logic can effectively be used without checking for a class mismatch
     *
     * @return void
     */
    private function initStoreScopeResolver()
    {
        if (null === $this->storeScopeResolver) {
            $this->storeScopeResolver = ObjectManager::getInstance()->get(StoreScopeResolverInterface::class);
        }
    }
}
