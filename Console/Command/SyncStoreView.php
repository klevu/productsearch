<?php
// Suppressing warning for old code during unrelated change
// phpcs:disable Generic.Metrics.NestingLevel.TooHigh

namespace Klevu\Search\Console\Command;

use Klevu\Logger\Api\StoreScopeResolverInterface;
use Klevu\Search\Model\Product\Sync as Sync;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\DriverInterface as FilesystemDriverInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Psr\Log\LoggerInterface as LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Klevu\Content\Model\ContentInterface as KlevuContent;

class SyncStoreView extends Command
{
    const LOCK_FILE = 'klevu_running_index.lock';
    const AREA_CODE_LOCK_FILE = 'klevu_areacode.lock';

    /**
     * Input arguments for mode setter command
     */
    const STORE_ARGUMENT = 'storecode';

    /**
     * @var AppState
     */
    protected $appState;

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
    private $logger;

    /**
     * @var StoreScopeResolverInterface
     */
    private $storeScopeResolver;

    /**
     * @var string|null
     */
    private $klevuLoggerFQCN;

    /**
     * @var FilesystemDriverInterface
     */
    private $fileDriver;

    /**
     * @var Sync
     */
    protected $sync;

    /**
     * @var KlevuContent
     */
    protected $cmsSync;

    /**
     * @var array
     */
    protected $websiteList = [];

    /**
     * @var array
     */
    protected $allStoreList = [];

    /**
     * @var array
     */
    protected $runStoreList = [];

    /**
     * @param AppState $appState
     * @param StoreManagerInterface $storeInterface
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param StoreScopeResolverInterface|null $storeScopeResolver
     * @param string|null $klevuLoggerFQCN
     * @param FilesystemDriverInterface|null $fileDriver
     */
    public function __construct(
        AppState $appState,
        StoreManagerInterface $storeInterface,
        DirectoryList $directoryList,
        LoggerInterface $logger,
        StoreScopeResolverInterface $storeScopeResolver = null,
        $klevuLoggerFQCN = null,
        FilesystemDriverInterface $fileDriver = null
    ) {
        $this->appState = $appState;
        $this->storeInterface = $storeInterface;
        $this->directoryList = $directoryList;
        $this->logger = $logger;
        $this->storeScopeResolver = $storeScopeResolver;
        if (is_string($klevuLoggerFQCN)) {
            $this->klevuLoggerFQCN = $klevuLoggerFQCN;
        }
        $this->fileDriver = $fileDriver ?: ObjectManager::getInstance()->get(FileDriver::class);

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $description = 'Sync recent changes to Product, Category and CMS data with Klevu for a particular store view '
            . 'or store views. When specifying mulitple stores, please use a comma to separate them and ensure all '
            . 'stores are within the same website.';
        $this->setName('klevu:syncstore:storecode')
            ->setDescription($description)
            ->setDefinition([
                new InputArgument(
                    self::STORE_ARGUMENT,
                    InputArgument::REQUIRED,
                    'The store code or codes (comma separated) to sync.'
                )
            ])
            ->setHelp(
                <<<HELP

To view the store list:
    <comment>%command.full_name% list</comment>

To sync a single store:
    <comment>%command.full_name% default</comment>

To sync multiple stores:
    <comment>%command.full_name% default,french</comment>

HELP
            );
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // See comments against methods for background. Ref: KS-7853
        $this->initLogger();
        $this->initStoreScopeResolver();

        $this->storeScopeResolver->setCurrentStoreById(0);
        $logDir = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $areacodeFile = $logDir . "/" . self::AREA_CODE_LOCK_FILE;
        try {
            if ($this->fileDriver->isExists($areacodeFile)) {
                $this->fileDriver->deleteFile($areacodeFile);
            }
            $this->appState->setAreaCode('frontend');
        } catch (\Exception $e) {
            $this->fileDriver->fileOpen($areacodeFile, 'w');
            $this->logger->error($e->getMessage());

            throw $e;
        }
        $storeList = $this->storeInterface->getStores();
        $syncFailed = $syncSuccess = [];
        $this->sync = ObjectManager::getInstance()->get(Sync::class);

        foreach ($storeList as $store) {
            $storeWebsiteId = $store->getWebsiteId();
            if (!isset($this->websiteList[$storeWebsiteId])) {
                $this->websiteList[$storeWebsiteId] = [];
            }

            $storeCode = $store->getCode();
            if (!in_array($storeCode, $this->websiteList[$storeWebsiteId], true)) {
                $this->websiteList[$storeWebsiteId][] = $storeCode;
            }
            $this->allStoreList[$storeCode] = $storeWebsiteId;
        }

        $storeCode = $input->getArgument(self::STORE_ARGUMENT);
        //$listStoreCode = $input->getOption(self::STORE_LIST_OPTION);
        if ($storeCode == "list") {
            $output->writeln("=== Available stores grouped by website ===");
            $output->writeln('');
            foreach ($this->websiteList as $websiteId => $websiteStores) {
                $output->writeln(
                    sprintf(
                        '<info>Website ID %s having store code(s): %s </info>',
                        $websiteId,
                        implode(',', $websiteStores)
                    )
                );
                $output->writeln('');
            }
            $nonConfiguredStores = $configuredStores = [];
            foreach ($storeList as $store) {
                $flag = $this->sync->isExtensionConfigured($store->getId());
                if ($flag) {
                    $configuredStores[] = $store->getCode();
                } else {
                    $nonConfiguredStores[] = $store->getCode();
                }
            }
            $output->writeln(sprintf(
                '<info>Klevu configured store code(s): %s</info>',
                implode(",", $configuredStores)
            ));
            $output->writeln(sprintf(
                '<info>Other(non-configured) store code(s): %s</info>',
                implode(",", $nonConfiguredStores)
            ));
        } else {
            $output->writeln("=== Starting storewise data sync ===");
            $output->writeln('');
            try {
                $originalStore = $this->storeScopeResolver->getCurrentStore();
                $array_store = explode(",", $storeCode);
                foreach ($array_store as $array_storeCode) {
                    $this->storeScopeResolver->setCurrentStoreByCode($array_storeCode);
                    $this->logger->info("SyncStoreView command executed via CLI");
                }

                $rejectedSites = $this->validateStoreCodes($array_store);
                if (!empty($rejectedSites)) {
                    $storeCodeError = sprintf(
                        'Error: Sync did not run for store code(s): %s. '
                        . 'Please ensure all store codes belong to the same website.',
                        implode(",", $rejectedSites)
                    );

                    foreach ($rejectedSites as $rejectedStoreCode) {
                        $this->storeScopeResolver->setCurrentStoreByCode($rejectedStoreCode);
                        $this->logger->debug($storeCodeError);
                    }

                    $output->writeln("<error>" . $storeCodeError . "</error>");
                    $output->writeln("");
                }
                $this->storeScopeResolver->setCurrentStore($originalStore);

                if (count($this->runStoreList) > 0) {
                    foreach ($this->runStoreList as $value) {
                        // Set the logger's store scope to the current store
                        $this->storeScopeResolver->setCurrentStoreByCode($value);

                        $file = $logDir . "/" . $value . "_" . self::LOCK_FILE;
                        $this->sync = ObjectManager::getInstance()->get(Sync::class);
                        $this->cmsSync = ObjectManager::getInstance()->get(KlevuContent::class);

                        if ($this->fileDriver->isExists($file)) {
                            $lockFileError = sprintf(
                                'Klevu index process cannot start because a lock file exists for store code: %s'
                                . ', skipping this store.',
                                $value
                            );
                            $this->logger->info($lockFileError);
                            $output->writeln('<error>'.$lockFileError.'</error>');
                            $output->writeln("");
                            $syncFailed[] = $value;
                            continue;
                        }

                        try {
                            $this->fileDriver->fileOpen($file, 'w');

                            $oneStore = $this->storeInterface->getStore($value);
                            //Sync Data
                            if (is_object($oneStore)) {
                                if (!$this->sync->setupSession($oneStore)) {
                                    if ($this->fileDriver->isExists($file)) {
                                        $this->fileDriver->deleteFile($file);
                                    }
                                    continue;
                                }

                                $productSyncStart = "Product Sync started for store code : " . $oneStore->getCode();
                                $this->logger->info($productSyncStart);
                                $output->writeln("<info>".$productSyncStart. "</info>");

                                $this->sync->runStore($oneStore);

                                $productSyncEnd = "Product Sync completed for store code : " . $oneStore->getCode();
                                $this->logger->info($productSyncEnd);
                                $output->writeln("<info>".$productSyncEnd . "</info>");
                                $output->writeln('');

                                $cmsSyncStart = "CMS Sync started for store code : " . $oneStore->getCode();
                                $this->logger->info($cmsSyncStart);
                                $output->writeln("<info>".$cmsSyncStart . "</info>");

                                $this->cmsSync->syncCmsData($oneStore);

                                $cmsSyncEnd = "CMS Sync completed for store code : " . $oneStore->getCode();
                                $this->logger->info($cmsSyncEnd);
                                $output->writeln("<info>".$cmsSyncEnd . "</info>");
                                $output->writeln('');
                                $syncSuccess[] = $oneStore->getCode();
                            }

                            $syncComplete =  "Sync was done for store code : ".$oneStore->getCode();
                            $this->logger->info($syncComplete);
                            $output->writeln("<info>".$syncComplete."</info>");
                            $output->writeln("<info>********************************</info>");
                        } catch (\Exception $e) {
                            $this->logger->error(sprintf(
                                "Error thrown in Storewise sync %s for STORE %s:",
                                $e->getMessage(),
                                $value
                            ));
                            $output->writeln(sprintf(
                                '<error>Error thrown in Storewise sync %s for STORE => %s</error>',
                                $e->getMessage(),
                                $value
                            ));
                        }

                        if ($this->fileDriver->isExists($file)) {
                            $this->fileDriver->deleteFile($file);
                        }
                    }

                    $this->storeScopeResolver->setCurrentStoreById(0);
                }
            } catch (\Exception $e) {
                $this->logger->error(sprintf("Error thrown in Storewise sync store %s:", $e->getMessage()));
                $output->writeln('<error>Error thrown in Storewise sync: ' . $e->getMessage() . '</error>');
                if (isset($file) && $this->fileDriver->isExists($file)) {
                    $this->fileDriver->deleteFile($file);
                }

                $this->storeScopeResolver->setCurrentStoreById(0);

                // we must have an exit code higher than zero to indicate something was wrong
                return Cli::RETURN_FAILURE;
            }
            $output->writeln('');

            if ($syncSuccess) {
                $originalStore = $this->storeScopeResolver->getCurrentStore();
                foreach ($syncSuccess as $successStoreCode) {
                    $this->storeScopeResolver->setCurrentStoreByCode($successStoreCode);
                    $this->logger->info(sprintf(
                        "Sync successfully completed for store code(s) %s:",
                        implode(",", $syncSuccess)
                    ));
                }
                $this->storeScopeResolver->setCurrentStore($originalStore);

                $output->writeln(sprintf(
                    '<info>Sync successfully completed for store code(s): %s</info>',
                    implode(",", $syncSuccess)
                ));
            }

            if ($syncFailed) {
                $originalStore = $this->storeScopeResolver->getCurrentStore();
                foreach ($syncFailed as $failedStoreCode) {
                    $this->storeScopeResolver->setCurrentStoreByCode($failedStoreCode);
                    $this->logger->info(sprintf(
                        "Sync did not complete for store code(s) %s:",
                        implode(',', $syncFailed)
                    ));
                }
                $this->storeScopeResolver->setCurrentStore($originalStore);

                $output->writeln(sprintf(
                    '<error>Sync did not complete for store code(s): %s</error>',
                    implode(',', $syncFailed)
                ));
            }
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param string[] $storeList
     * @return array
     */
    private function validateStoreCodes($storeList)
    {
        $firstWebsite = null;
        $rejectedStores = [];
        if (!is_array($storeList)) {
            return $storeList;
        }
        foreach ($storeList as $storeCode) {
            //check if store code is valid
            if (isset($this->allStoreList[$storeCode])) {
                //if it is the first website
                if (null === $firstWebsite) {
                    $firstWebsite = $this->allStoreList[$storeCode];
                    $this->runStoreList[] = $storeCode;
                } else {
                    if (!isset($this->allStoreList[$storeCode]) || $firstWebsite != $this->allStoreList[$storeCode]) {
                        $rejectedStores[] = $storeCode;
                    } else {
                        $this->runStoreList[] = $storeCode;
                    }
                }
            } else {
                $rejectedStores[] = $storeCode;
            }
        }
        return $rejectedStores;
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
        if (!($this->logger instanceof LoggerInterface)) {
            $objectManager = ObjectManager::getInstance();
            if ($this->klevuLoggerFQCN && !($this->logger instanceof $this->klevuLoggerFQCN)) {
                $this->logger = $objectManager->get($this->klevuLoggerFQCN);
            } elseif (!$this->logger) {
                $this->logger = $objectManager->get(LoggerInterface::class);
            }
        }
    }

    /**
     * Instantiate the StoreScopeResolver property
     *
     * For the same reasons as initLogger is required, we can't inject a class from a new
     *  module into a CLI command. Unlike initLogger, however, this is a new property so
     *  the usual $this->storeScopeResolver =
     *      $storeScopeResolver ?: ObjectManager::getInstance()->get(StoreScopeResolverInterface::class)
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
