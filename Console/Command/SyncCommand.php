<?php

namespace Klevu\Search\Console\Command;

use Exception;
use Klevu\Logger\Api\StoreScopeResolverInterface;
use Klevu\Search\Model\Order\Sync as Order;
use Klevu\Search\Model\Product\MagentoProductActionsInterface as MagentoProductActions;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Shell;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Psr\Log\LoggerInterface as LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder as PhpExecutableFinderFactory;

/**
 * Class SyncCommand
 * @package Klevu\Search\Console\Command
 */
class SyncCommand extends Command
{
    const LOCK_FILE = 'klevu_running_index.lock';
    const AREA_CODE_LOCK_FILE = 'klevu_areacode.lock';

    const ALLDATA_DESC = 'Send all items to Klevu.';
    const UPDATESONLY_DESC = 'Only send items which have been modified since the last sync with Klevu.';

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
     * @var ShellInterface
     */
    protected $_shell;

    /**
     * @var \Symfony\Component\Process\PhpExecutableFinder
     */
    protected $_phpExecutableFinder;

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
     * @var array
     */
    protected $websiteList = array();

    /**
     * @var array
     */
    protected $allStoreList = array();

    /**
     * @var array
     */
    protected $runStoreList = array();

    /**
     * @param AppState $appState
     * @param StoreManagerInterface $storeInterface
     * @param DirectoryList $directoryList
     * @param Shell $shell
     * @param PhpExecutableFinderFactory $phpExecutableFinderFactory
     * @param LoggerInterface $logger
     * @param StoreScopeResolverInterface|null $storeScopeResolver
     * @param string|null $klevuLoggerFQCN
     */
    public function __construct(
        AppState $appState,
        StoreManagerInterface $storeInterface,
        DirectoryList $directoryList,
        Shell $shell,
        PhpExecutableFinderFactory $phpExecutableFinderFactory,
        LoggerInterface $logger,
        StoreScopeResolverInterface $storeScopeResolver = null,
        $klevuLoggerFQCN = null
    ) {
        $this->appState = $appState;
        $this->directoryList = $directoryList;
        $this->storeInterface = $storeInterface;
        $this->_shell = $shell;
        $this->_phpExecutableFinder = $phpExecutableFinderFactory;
        $this->logger = $logger;
        $this->storeScopeResolver = $storeScopeResolver;
        if (is_string($klevuLoggerFQCN)) {
            $this->klevuLoggerFQCN = $klevuLoggerFQCN;
        }

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('klevu:syncdata')
            ->setDescription('
            Sync Product, Category, CMS and Order data with Klevu for all stores.
            A separate thread will be initialised to sync each website independently to process the stores within.
            You can specify whether to process all items or just those that have changed via an option detailed below.
            If no option is specified, --updatesonly will be used.')
            ->setDefinition($this->getInputList())
            ->setHelp(
                <<<HELP

Only send items which have been modified since the last sync with Klevu:
    <comment>%command.full_name% --updatesonly</comment>

Send all items to Klevu:
    <comment>%command.full_name% --alldata</comment>

HELP
            );
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // See comments against methods for background. Ref: KS-7853
        $this->initLogger();
        $this->initStoreScopeResolver();

        $this->storeScopeResolver->setCurrentStoreById(0);
        $logDir = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $file = $logDir . "/" . self::LOCK_FILE;
        $areacodeFile = $logDir . "/" . self::AREA_CODE_LOCK_FILE;
        try {
            if (file_exists($areacodeFile)) {
                unlink($areacodeFile);
            }
            $this->appState->setAreaCode('frontend');
        } catch (\Exception $e) {
            fopen($areacodeFile, 'w');
            $this->logger->error($e->getMessage());
            throw $e;
        }
        if (file_exists($file)) {
            $output->writeln('<error>Klevu index process cannot start because a lock file exists. Sync process did not run.</error>');
            $output->writeln('');
            $output->writeln('<comment>Remove any Klevu associated .lock files from the magento-root/var/ directory and then perform sync again.</comment>');
            return;
        }

        fopen($file, 'w');

        try {
            $storeList = $this->storeInterface->getStores();
            $lockStoresList = array();
            foreach ($storeList as $store) {
                //Checking for each and every store. Stores having lock file will be skipping...
                $storeFile = $logDir . "/" . $store->getCode() . "_" . self::LOCK_FILE;
                if (file_exists($storeFile)) {
                    $lockStoresList[] = $store->getCode();
                    continue;
                }

                if (!isset($this->websiteList[$store->getWebsiteId()])) {
                    $this->websiteList[$store->getWebsiteId()] = array();
                }
                $this->websiteList[$store->getWebsiteId()] = array_unique(array_merge($this->websiteList[$store->getWebsiteId()], array($store->getCode())));
                $this->allStoreList[$store->getCode()] = $store->getWebsiteId();
            }

            //Showing the message for those stores which are having locks, if all of them having stores then return
            if(is_array($lockStoresList) && !empty($lockStoresList)){
                $output->writeln('<error>Error: Klevu index process cannot start because a lock file exists for store code(s): ' . implode(",", $lockStoresList) . ', skipping this store(s).</error>');
                $output->writeln('');
                $output->writeln('<comment>Remove Klevu associated .lock files from the magento-root/var/ directory and then perform sync again.</comment>');
                $output->writeln('');
                if(empty($this->websiteList)){
                    $output->writeln('<error>Sync did not run for any of the stores...</error>');
                    $output->writeln('');
                    return 0;
                }
            }

            $output->writeln('=== Synchronization process started ===');
            //Getting MagentoProductActions class
            $magentoProductActions = ObjectManager::getInstance()->get(MagentoProductActions::class);
            $catalogNotifyMsg = 'NOTE: This process can take a while as it depends on catalog size and number of stores';
            $output->writeln('<comment>' . $catalogNotifyMsg . '</comment>');
            $output->writeln('');
            if ($input->hasParameterOption('--alldata')) {
                $output->writeln('<info>Synchronization started using --alldata option.</info>');
                $magentoProductActions->markAllProductsForUpdate();
            } elseif ($input->hasParameterOption('--updatesonly')) {
                $output->writeln('<info>Synchronization started using --updatesonly option.</info>');
            } else {
                $output->writeln('<info>No option provided. Synchronization started using updatesonly option.</info>');
            }

            $webKey = 1;
            //get php executable
            $phpPath = $this->_phpExecutableFinder->find() ?: 'php';
            foreach ($this->websiteList as $storeList) {
                $output->writeln('<info>' . $webKey . '. Started for store code(s): ' . implode(",", $storeList) . '</info>');

                $originalStore = $this->storeScopeResolver->getCurrentStore();
                foreach ($storeList as $storeCode) {
                    $this->storeScopeResolver->setCurrentStoreByCode($storeCode);
                    $this->logger->info(sprintf(
                        "Synchronize product data for the specified store codes: %s /bin/magento klevu:syncstore:storecode %s",
                        $phpPath,
                        implode(',', $storeList)
                    ));
                }
                $this->storeScopeResolver->setCurrentStore($originalStore);

                $this->_shell->execute(
                    $phpPath . ' %s klevu:syncstore:storecode ' . implode(",", $storeList),
                    [
                        BP . '/bin/magento'
                    ]
                );
                $output->writeln('<info>   Completed for store code(s): ' . implode(",", $storeList) . '</info>');
                $output->writeln('');
                $webKey++;
            }

            // sync cms data moved to the klevu:syncstore:storecode command
            /*$sync = ObjectManager::getInstance()->get(Content::class);
            $sync->run();*/

            // sync order data
            $syncOrder = ObjectManager::getInstance()->get(Order::class);
            $syncOrder->run();

            $klevusession = ObjectManager::getInstance()->get('Klevu\Search\Model\Session');
            if ($input->hasParameterOption('--alldata')) {
                $output->writeln('<info>All Data has been synchronized with Klevu</info>');
            } elseif ($input->hasParameterOption('--updatesonly')) {
                if ($klevusession->getKlevuFailedFlag() == 1) {
                    $this->logger->error("Product sync failed using SyncCommand. Please consult var/log/Klevu_Search.log file for more information.");
                    $output->writeln("<error>Data sync failed. Please consult var/log/Klevu_Search.log file for more information.</error>");
                    $klevusession->setKlevuFailedFlag(0);
                } else {
                    $this->logger->info("Data updates have been sent to Klevu using SyncCommand.");
                    $output->writeln('<info>Data updates have been sent to Klevu</info>');
                    $klevusession->setKlevuFailedFlag(0);
                }
            } else {
                $this->logger->info("Data updates have been sent to Klevu.");
                $output->writeln('<info>Data updates have been sent to Klevu</info>');
                $klevusession->setKlevuFailedFlag(0);
            }
        } catch (LocalizedException $e) {
            $this->logger->error(sprintf("LocalizedException %s",$e->getMessage()));
            $output->writeln('<error>LocalizedException: ' . $e->getMessage() . '</error>');
        } catch (Exception $e) {
            $this->logger->error(sprintf("Exception: Not able to complete the synchronization due to %s",$e->getMessage()));
            $output->writeln('<error>Exception: Not able to complete the synchronization due to ' . $e->getMessage() . '</error>');
        }

        if (file_exists($file)) {
            unlink($file);
        }

        return Cli::RETURN_SUCCESS;
    }

    public function getInputList()
    {
        $inputList = [];

        $inputList[] = new InputOption(
            'updatesonly',
            null,
            InputOption::VALUE_OPTIONAL,
            self::UPDATESONLY_DESC
        );

        $inputList[] = new InputOption(
            'alldata',
            null,
            InputOption::VALUE_OPTIONAL,
            self::ALLDATA_DESC
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
     *  the usual $this->>storeScopeResolver = $storeScopeResolver ?: ObjectManager::getInstance()->get(StoreScopeResolverInterface::class)
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
