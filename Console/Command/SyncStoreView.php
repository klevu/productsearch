<?php

namespace Klevu\Search\Console\Command;

use Klevu\Search\Model\Product\Sync as Sync;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State as AppState;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Psr\Log\LoggerInterface as LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\ObjectManagerInterface;
use Klevu\Content\Model\ContentInterface as KlevuContent;
use Klevu\Search\Helper\Data as KlevuSearchHelperData;

/**
 * Class SyncStoreView
 * @package Klevu\Search\Console\Command
 */
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
     * @var DirectoryList
     */
    protected $sync;

    protected $websiteList = array();
    protected $allStoreList = array();
    protected $runStoreList = array();
    private $_logger;

    /**
     * Inject dependencies
     *
     * @param AppState $appState
     * @param StoreManagerInterface $storeInterface
     * @param DirectoryList $directoryList
     * @param Sync $sync
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        AppState $appState,
        StoreManagerInterface $storeInterface,
        DirectoryList $directoryList,
        LoggerInterface $logger
    )
    {
        $this->appState = $appState;
        $this->directoryList = $directoryList;
        $this->storeInterface = $storeInterface;
        $this->_logger = $logger;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $description = 'Sync recent changes to Product, Category and CMS data with Klevu for a particular store view or store views. When specifying mulitple stores, please use a comma to separate them and ensure all stores are within the same website.';
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
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logDir = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $areacodeFile = $logDir . "/" . self::AREA_CODE_LOCK_FILE;
        try {
            if (file_exists($areacodeFile)) {
                unlink($areacodeFile);
            }
            $this->appState->setAreaCode('frontend');
        } catch (\Exception $e) {
            fopen($areacodeFile, 'w');
            $this->_logger->critical($e->getMessage());
            throw $e;
        }
        $storeList = $this->storeInterface->getStores();
        $syncFailed = $syncSuccess = array();
        $this->sync = ObjectManager::getInstance()->get(Sync::class);
        $this->klevuSearchHelperData = ObjectManager::getInstance()->get(KlevuSearchHelperData::class);
        $this->klevuSearchHelperData->log(\Zend\Log\Logger::INFO,"SyncStoreView command executed via CLI");
        foreach ($storeList as $store) {
            if (!isset($this->websiteList[$store->getWebsiteId()])) $this->websiteList[$store->getWebsiteId()] = array();
            $this->websiteList[$store->getWebsiteId()] = array_unique(array_merge($this->websiteList[$store->getWebsiteId()], array($store->getCode())));
            $this->allStoreList[$store->getCode()] = $store->getWebsiteId();
        }

        $storeCode = $input->getArgument(self::STORE_ARGUMENT);
        //$listStoreCode = $input->getOption(self::STORE_LIST_OPTION);
        if ($storeCode == "list") {
            $output->writeln("=== Available stores grouped by website ===");
            $output->writeln('');
            foreach ($this->websiteList as $websiteId => $websiteStores) {

                $output->writeln("<info>Website ID " . $websiteId . " having store code(s): " . implode(",", $websiteStores) . " </info>");
                $output->writeln('');
            }
            $nonConfiguredStores = $configuredStores = array();
            foreach ($storeList as $store) {
                $flag = $this->sync->isExtensionConfigured($store->getId());
                if ($flag) {
                    $configuredStores[] = $store->getCode();
                } else {
                    $nonConfiguredStores[] = $store->getCode();
                }
            }
            $output->writeln("<info>Klevu configured store code(s): " . implode(",", $configuredStores) . "</info>");
            $output->writeln("<info>Other(non-configured) store code(s): " . implode(",", $nonConfiguredStores) . "</info>");

        } else {
            $output->writeln("=== Starting storewise data sync ===");
            $output->writeln('');
            try {
                $array_store = explode(",", $storeCode);
                $rejectedSites = $this->validateStoreCodes($array_store);


                if (!empty($rejectedSites)) {
                    $storeCodeError = "Error: Sync did not run for store code(s): " . implode(",", $rejectedSites) . ". Please ensure all store codes belong to the same website.";
                    $this->klevuSearchHelperData->log(\Zend\Log\Logger::DEBUG, $storeCodeError);
                    $output->writeln("<error>" . $storeCodeError . "</error>");
                    $output->writeln("");
                }
                if (count($this->runStoreList) > 0) {
                    foreach ($this->runStoreList as $value) {
                       $file = $logDir . "/" . $value . "_" . self::LOCK_FILE;
                  $this->sync = ObjectManager::getInstance()->get(Sync::class);
                  $this->cmsSync = ObjectManager::getInstance()->get(KlevuContent::class);

                        if (file_exists($file)) {
                            $lockFileError = "Klevu index process cannot start because a lock file exists for store code: ' . $value . ', skipping this store.";
                            $this->klevuSearchHelperData->log(\Zend\Log\Logger::INFO,$lockFileError);
                            $output->writeln('<error>'.$lockFileError.'</error>');
                            $output->writeln("");
                            $syncFailed[] = $value;
                            continue;
                        }
                        fopen($file, 'w');

                        try {
                            $oneStore = $this->storeInterface->getStore($value);
                            //Sync Data
                            if (is_object($oneStore)) {
                                if (!$this->sync->setupSession($oneStore)) {
                                    if (file_exists($file)) {
                                        unlink($file);
                                    }
                                    continue;
                                }

                                $productSyncStart = "Product Sync started for store code : " . $oneStore->getCode();
                                $this->klevuSearchHelperData->log(\Zend\Log\Logger::INFO,$productSyncStart);
                                $output->writeln("<info>".$productSyncStart. "</info>");

                                $this->sync->runStore($oneStore);

                                $productSyncEnd = "Product Sync completed for store code : " . $oneStore->getCode();
                                $this->klevuSearchHelperData->log(\Zend\Log\Logger::INFO,$productSyncEnd);
                                $output->writeln("<info>".$productSyncEnd . "</info>");
                                $output->writeln('');

                                $cmsSyncStart = "CMS Sync started for store code : " . $oneStore->getCode();
                                $this->klevuSearchHelperData->log(\Zend\Log\Logger::INFO,$cmsSyncStart);
                                $output->writeln("<info>".$cmsSyncStart . "</info>");

                                $this->cmsSync->syncCmsData($oneStore);

                                $cmsSyncEnd = "CMS Sync completed for store code : " . $oneStore->getCode();
                                $this->klevuSearchHelperData->log(\Zend\Log\Logger::INFO,$cmsSyncEnd);
                                $output->writeln("<info>".$cmsSyncEnd . "</info>");
                                $output->writeln('');
                                $syncSuccess[] = $oneStore->getCode();
                            }

                            $syncComplete =  "Sync was done for store code : ".$oneStore->getCode();
                            $this->klevuSearchHelperData->log(\Zend\Log\Logger::INFO,$syncComplete);
                            $output->writeln("<info>".$syncComplete."</info>");
                            $output->writeln("<info>********************************</info>");
                        } catch (\Exception $e) {
                            $this->klevuSearchHelperData->log(\Zend\Log\Logger::ERR,sprintf("Error thrown in Storewise sync %s for STORE %s:",$e->getMessage(),$value));
                            $output->writeln('<error>Error thrown in Storewise sync ' . $e->getMessage() ." for STORE => ".$value. '</error>');
                        }

                        if (file_exists($file)) {
                            unlink($file);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->klevuSearchHelperData->log(\Zend\Log\Logger::DEBUG,sprintf("Error thrown in Storewise sync store %s:",$e->getMessage()));
                $output->writeln('<error>Error thrown in Storewise sync: ' . $e->getMessage() . '</error>');
                if(isset($file)) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
                // we must have an exit code higher than zero to indicate something was wrong
                return \Magento\Framework\Console\Cli::RETURN_FAILURE;
            }
            $output->writeln('');
            if (!empty($syncSuccess)) {
                $this->klevuSearchHelperData->log(\Zend\Log\Logger::INFO,sprintf("Sync successfully completed for store code(s) %s:",implode(",", $syncSuccess)));
                $output->writeln('<info>Sync successfully completed for store code(s): ' . implode(",", $syncSuccess) . '</info>');
            }
            if (!empty($syncFailed)) {
                $this->klevuSearchHelperData->log(\Zend\Log\Logger::DEBUG,sprintf("Sync did not complete for store code(s) %s:", implode(",", $syncFailed)));
                $output->writeln('<error>Sync did not complete for store code(s): ' . implode(",", $syncFailed) . '</error>');
            }
        }
    }

    /**
     * @param $storeList
     * @return array
     */
    private function validateStoreCodes($storeList)
    {
        $firstWebsite = null;
        $rejectedStores = array();
        if (!is_array($storeList)) return $storeList;
        foreach ($storeList as $storeCode) {
            //check if store code is valid
            if (isset($this->allStoreList[$storeCode])) {
                //if it is the first website
                if (is_null($firstWebsite)) {
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
}


