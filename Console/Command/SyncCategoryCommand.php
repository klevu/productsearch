<?php

namespace Klevu\Search\Console\Command;


use Klevu\Search\Model\Product\MagentoProductActionsInterface as MagentoProductActions;
use Klevu\Search\Model\Product\KlevuProductActionsInterface as KlevuProductActions;
use Klevu\Search\Model\Product\Sync as Sync;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Psr\Log\LoggerInterface as LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SyncCategoryCommand
 * @package Klevu\Search\Console\Command
 */
class SyncCategoryCommand extends Command
{
    const LOCK_FILE = 'category_klevu_running_index.lock';
    const AREA_CODE_LOCK_FILE = 'klevu_category_areacode.lock';

    const ALLDATA_CATEGORY_DESC = 'Send all Category records to Klevu.';
    const UPDATESONLY_CATEGORY_DESC = 'Only send those Category records which have been modified since the last sync with Klevu.';

    /**
     * @var Sync
     */
    protected $sync;

    /**
     * @var MagentoProductActions
     */
    protected $magentoProductActions;


    /**
     * @var KlevuProductActions
     */
    protected $klevuProductActions;

    /**
     * SyncCategoryCommand constructor.
     *
     * @param State $state
     * @param StoreManagerInterface $storeInterface
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     */
    public function __construct(
        State $state,
        StoreManagerInterface $storeInterface,
        DirectoryList $directoryList,
        LoggerInterface $logger
    )
    {
        $this->state = $state;
        $this->directoryList = $directoryList;
        $this->storeInterface = $storeInterface;
        $this->_logger = $logger;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('klevu:sync:category')
            ->setDescription('
            Sync Category data with Klevu for all stores.
            You can specify whether to process all categories or just those that have changed via an option detailed below.
            If no option is specified, --updatesonly will be used.')
            ->setDefinition($this->getInputList())
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
        $logDir = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $storeLockFile = '';
        $areaCodeFile = $logDir . "/" . self::AREA_CODE_LOCK_FILE;
        try {
            if (file_exists($areaCodeFile)) {
                unlink($areaCodeFile);
            }
            $this->state->setAreaCode(Area::AREA_FRONTEND);
        } catch (LocalizedException $e) {
            fopen($areaCodeFile, 'w');
            if ($this->state->getAreaCode() != Area::AREA_FRONTEND) {
                $output->writeln(__(
                    sprintf('Category sync running in an unexpected state AreaCode : (%s)', $this->state->getAreaCode())
                )->getText());
            }
        }

        $storeList = $this->storeInterface->getStores();
        $syncFailed = $syncSuccess = array();
        $storeCodesAll = array();
        foreach ($storeList as $store) {
            if (!isset($this->websiteList[$store->getWebsiteId()])) $this->websiteList[$store->getWebsiteId()] = array();
            $this->websiteList[$store->getWebsiteId()] = array_unique(array_merge($this->websiteList[$store->getWebsiteId()], array($store->getCode())));
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
                $output->writeln('<info>No option provided. Category Synchronization started using updatesonly option.</info>');
            }

            if (count($storeCodesAll) > 0) {
                foreach ($storeCodesAll as $rowStoreCode) {

                    $storeLockFile = $logDir . "/" . $rowStoreCode . "_" . self::LOCK_FILE;
                    if (file_exists($storeLockFile)) {
                        $output->writeln('<error>Klevu Category sync process cannot start because a lock file exists for store code: ' . $rowStoreCode . ', skipping this store.</error>');
                        $output->writeln("");
                        $syncFailed[] = $rowStoreCode;
                        continue;
                    }
                    fopen($storeLockFile, 'w');
                    $rowStoreObject = $this->storeInterface->getStore($rowStoreCode);
                    if (!is_object($rowStoreObject)) {
                        $output->writeln('<error>Store object found invalid for store code : ' . $rowStoreCode . ', skipping this store.</error>');
                        $output->writeln("");
                        $syncFailed[] = $rowStoreCode;
                        continue;
                    }

                    if (!$this->klevuProductActions->setupSession($rowStoreObject)) {
                        if (file_exists($storeLockFile)) {
                            unlink($storeLockFile);
                        }
                        return;
                    }

                    $output->writeln('');
                    $output->writeln("<info>Category Sync started for store code : " . $rowStoreObject->getCode() . "</info>");
                    $msg = $this->sync->runCategory($rowStoreObject);
                    if (!empty($msg)) {
                        $output->writeln("<comment>" . $msg . "</comment>");
                    }
                    $output->writeln("<info>Category Sync completed for store code : " . $rowStoreObject->getCode() . "</info>");

                    $syncSuccess[] = $rowStoreObject->getCode();

                    if (file_exists($storeLockFile)) {
                        unlink($storeLockFile);
                    }
                    $output->writeln("<info>********************************</info>");
                }
            }

        } catch (\Exception $e) {
            $output->writeln('<error>Error thrown in store wise Category data sync: ' . $e->getMessage() . '</error>');
            if (isset($storeLockFile)) {
                if (file_exists($storeLockFile)) {
                    unlink($storeLockFile);
                }
            }
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
        $output->writeln('');
        if (!empty($syncSuccess)) {
            $output->writeln('<info>Category Sync successfully completed for store code(s): ' . implode(",", $syncSuccess) . '</info>');
        }
        if (!empty($syncFailed)) {
            $output->writeln('<error>Category Sync did not complete for store code(s): ' . implode(",", $syncFailed) . '</error>');
        }
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

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

}

