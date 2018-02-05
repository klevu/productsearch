<?php
namespace Klevu\Search\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Klevu\Search\Model\Product\Sync as Sync;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\ObjectManager;

class SyncStoreView extends Command
{

    const LOCK_FILE = 'klevu_running_index.lock';
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

    /**
     * Inject dependencies
     *
     * @param AppState $appState
     * @param StoreManagerInterface $storeInterface
     * @param DirectoryList $directoryList
     * @param Sync $sync
     */
    public function __construct(
        AppState $appState,
        StoreManagerInterface $storeInterface,
        DirectoryList $directoryList
    )
    {
        $this->appState = $appState;
        $this->directoryList = $directoryList;
        $this->storeInterface = $storeInterface;
        parent::__construct();
    }
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $description = 'Set store code to sync data.';
        $this->setName('klevu:syncstore:storecode')
            ->setDescription($description)
            ->setDefinition([
                new InputArgument(
                    self::STORE_ARGUMENT,
                    InputArgument::REQUIRED,
                    'Set store code to sync data.'
                )
            ]);
        parent::configure();
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode('frontend');
        $storeList = $this->storeInterface->getStores();

        foreach ($storeList as $store) {
            if(!isset($this->websiteList[$store->getWebsiteId()])) $this->websiteList[$store->getWebsiteId()] = array();
            $this->websiteList[$store->getWebsiteId()] = array_unique(array_merge($this->websiteList[$store->getWebsiteId()], array($store->getCode())));
            $this->allStoreList[$store->getCode()] = $store->getWebsiteId();
        }
        $storeCode = $input->getArgument(self::STORE_ARGUMENT);
        if($storeCode == "list") {
            $output->writeln("<info>Available stores grouped by website: </info>");
            foreach ($this->websiteList as $websiteId => $websiteStores) {
                $output->writeln("<info>Website ID ".$websiteId." : ".implode(",",$websiteStores)." </info>");
            }

        } else {
            try {
                $array_store = explode(",",$storeCode);
                $rejectedSites = $this->validateStoreCodes($array_store);
                $logDir = $this->directoryList->getPath(DirectoryList::VAR_DIR);

                $this->sync = ObjectManager::getInstance()->get(Sync::class);

                if(!empty($rejectedSites))
                    $output->writeln("<error>Sync can not be done for store codes. Please check if that following codes belong to one website: ".implode(",",$rejectedSites)."</error>");
                if(count($this->runStoreList) > 0 ) {
                    foreach($this->runStoreList as $value) {
                        $file = $logDir."/".$value."_".self::LOCK_FILE;
                        if (file_exists($file)) {
                            $output->writeln('<info>Klevu indexing process is in running state for store code '.$value.'</info>');
                            continue;
                        }
                        fopen($file, 'w');

                        try {
                            $oneStore = $this->storeInterface->getStore($value);
                            //Sync Data
                            if(is_object($oneStore)) {
                                if (!$this->sync->setupSession($oneStore)) {
                                    if (file_exists($file)) {
                                        unlink($file);
                                    }
                                    continue;
                                }
                                $this->sync->runStore($oneStore);
                            }
                            $output->writeln("<info>Sync was done for store code : ".$oneStore->getCode()."</info>");
                        } catch (\Exception $e) {
                            $output->writeln('<error>' . $e->getMessage() ." :".$value. '</error>');
                        }

                        if (file_exists($file)) {
                            unlink($file);
                        }
                    }
                }
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                if(isset($file)) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }

                // we must have an exit code higher than zero to indicate something was wrong
                return \Magento\Framework\Console\Cli::RETURN_FAILURE;
            }
            $output->write("\n");
            $output->writeln("<info>Sync was completed.</info>");
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
        if(!is_array($storeList)) return $storeList;
        foreach ($storeList as $storeCode){
            //check if store code is valid
            if(isset($this->allStoreList[$storeCode])){
                //if it is the first website
                if(is_null($firstWebsite)) {
                    $firstWebsite = $this->allStoreList[$storeCode];
                    $this->runStoreList[] = $storeCode;
                } else {
                    if( !isset($this->allStoreList[$storeCode]) || $firstWebsite != $this->allStoreList[$storeCode] ) {
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