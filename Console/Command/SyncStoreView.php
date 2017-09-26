<?php
namespace Klevu\Search\Console\Command;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Exception;
use Klevu\Search\Model\Product\Sync as Sync;
use Klevu\Search\Model\Order\Sync as Order;
use Klevu\Content\Model\Content;
use Magento\Framework\App\Filesystem\DirectoryList;
use Klevu\Search\Helper\Api as Api;
use Klevu\Search\Model\Session as Session;
use Klevu\Search\Helper\Config as Config;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
   
class SyncStoreView extends Command
{
	 
	const LOCK_FILE = 'klevu_running_index.lock';
    /**
     * Input arguments for mode setter command
     */
    const STORE_ARGUMENT = 'storecode';
    
    /**
     * Object manager factory
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * Inject dependencies
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
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
        try {
			$state = ObjectManager::getInstance()->get('\Magento\Framework\App\State');
            $state->setAreaCode('frontend');
			$stores = ObjectManager::getInstance()->get(StoreManagerInterface::class)->getStores();
            $storeCode = $input->getArgument(self::STORE_ARGUMENT);
			if($storeCode == "list") {
			    $codeList = array();
				foreach ($stores as $store) {
                    $codeList[] = $store->getCode();
				}
                $output->writeln("Available stores : ".implode(",",$codeList));
			} else {
				$array_store = explode(",",$storeCode);
				foreach($array_store as $key => $value) {
					$directoryList = ObjectManager::getInstance()->get(DirectoryList::class);
					$logDir = $directoryList->getPath(DirectoryList::VAR_DIR);
					$dir = $logDir."/".$value."_".self::LOCK_FILE;
					if (file_exists($dir)) {
						$output->writeln('<info>Klevu indexing process is in running state</info>');
                        continue;
					}
					fopen($dir, 'w');
					
					try {
						$onestore = ObjectManager::getInstance()->get(StoreManagerInterface::class)->getStore($value);
						//Sync Data
						if(is_object($onestore)) {
							$sync = ObjectManager::getInstance()->get(Sync::class);
							if (!$sync->setupSession($onestore)) {
                                if (file_exists($dir)) {
                                    unlink($dir);
                                }
								continue;
							}
							$sync->syncData($onestore);
							$sync->runCategory($onestore);
						}
						$output->writeln("Sync was done for store code :".$value);
					} catch (\Exception $e) {
						 $output->writeln('<error>' . $e->getMessage() ." :".$value. '</error>');
					}
					
					if (file_exists($dir)) {
						unlink($dir);
					}
				}
			}
            
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }
}