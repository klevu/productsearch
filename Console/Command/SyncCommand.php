<?php
namespace Klevu\Search\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use Symfony\Component\Process\PhpExecutableFinder as PhpExecutableFinderFactory;
use Magento\Framework\Shell;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use Klevu\Search\Model\Product\Sync;
use Klevu\Content\Model\Content;
use Klevu\Search\Model\Order\Sync as Order;
use Klevu\Content\Model\ContentInterface;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Magento\Framework\App\State as AppState;
use Klevu\Search\Model\Product\MagentoProductActionsInterface as MagentoProductActions;
use \Psr\Log\LoggerInterface as LoggerInterface;

class SyncCommand extends Command
{
    const LOCK_FILE = 'klevu_running_index.lock';
	
	const AREA_CODE_LOCK_FILE = 'klevu_areacode.lock';
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
	
	private $_logger;

    protected $websiteList = array();
    protected $allStoreList = array();
    protected $runStoreList = array();


    public function __construct(
        AppState $appState,
        StoreManagerInterface $storeInterface,
        DirectoryList $directoryList,
        Shell $shell,
        PhpExecutableFinderFactory $phpExecutableFinderFactory,
		LoggerInterface $logger
    )
    {
        $this->appState = $appState;
        $this->directoryList = $directoryList;
        $this->storeInterface = $storeInterface;
        $this->_shell = $shell;
        $this->_phpExecutableFinder = $phpExecutableFinderFactory;
		$this->_logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('klevu:syncdata')
            ->setDescription('Sync product and content Data With klevu.')
            ->setDefinition($this->getInputList());
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$logDir = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $file = $logDir."/".self::LOCK_FILE;
		$areacodeFile = $logDir."/".self::AREA_CODE_LOCK_FILE;
		try {
			if(file_exists($areacodeFile)){
				unlink($areacodeFile);
			}
			$this->appState->setAreaCode('frontend');
		}catch (\Exception $e) {
			fopen($areacodeFile, 'w');
		    $this->_logger->critical($e->getMessage());
		    throw $e; return false;
		}
        if (file_exists($file)) {
            $output->writeln('<info>Klevu indexing process is in running state</info>');
            return;
        }
		
        fopen($file, 'w');

        try {
            $storeList = $this->storeInterface->getStores();

            foreach ($storeList as $store) {
                if(!isset($this->websiteList[$store->getWebsiteId()])) $this->websiteList[$store->getWebsiteId()] = array();
                $this->websiteList[$store->getWebsiteId()] = array_unique(array_merge($this->websiteList[$store->getWebsiteId()], array($store->getCode())));
                $this->allStoreList[$store->getCode()] = $store->getWebsiteId();
            }
            // sync cms data
            $magentoProductActions = ObjectManager::getInstance()->get(MagentoProductActions::class);

            if ($input->hasParameterOption('--alldata')) {
                $magentoProductActions->markAllProductsForUpdate();
            }
            //get php executable
            $phpPath = $this->_phpExecutableFinder->find() ?: 'php';
            foreach ($this->websiteList as $storeList){
                $output->writeln('<info>Synchronize product data for the store codes '.implode(",",$storeList).'</info>');
                $this->_shell->execute(
                    $phpPath . ' %s klevu:syncstore:storecode '.implode(",",$storeList),
                    [
                        BP . '/bin/magento'
                    ]
                );
            }

            // sync cms data moved to the klevu:syncstore:storecode command
            /*$sync = ObjectManager::getInstance()->get(Content::class);
            $sync->run();*/

            // sync order data
            $syncOrder = ObjectManager::getInstance()->get(Order::class);
            $syncOrder->run();

            $klevusession = ObjectManager::getInstance()->get('Klevu\Search\Model\Session');

            if ($input->hasParameterOption('--alldata')) {

                $output->writeln('<info>All Data have been sent to Klevu</info>');

            } elseif ($input->hasParameterOption('--updatesonly')) {

                if($klevusession->getKlevuFailedFlag() == 1){
                    $output->writeln("<info>Product sync failed.Please consult klevu_search.log file for more information.</info>");
                    $klevusession->setKlevuFailedFlag(0);
                } else {
                    $output->writeln('<info>Data updates have been sent to Klevu</info>');
                    $klevusession->setKlevuFailedFlag(0);
                }
            }
        } catch (LocalizedException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        } catch (Exception $e) {
            $output->writeln('<error>Not able to update</error>');
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function getInputList()
    {
        $inputList = [];

        $inputList[] = new InputOption(
            'updatesonly',
            null,
            InputOption::VALUE_OPTIONAL,
            'Data updates have been sent to Klevu',
            'updatesonly'
        );

        $inputList[] = new InputOption(
            'alldata',
            null,
            InputOption::VALUE_OPTIONAL,
            'All Data have been sent to Klevu',
            'alldata'
        );

        return $inputList;
    }

}
