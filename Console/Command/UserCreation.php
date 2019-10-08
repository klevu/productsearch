<?php
namespace Klevu\Search\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use Klevu\Search\Helper\Api\Proxy as Api;
use Klevu\Search\Helper\Config as Config;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use \Psr\Log\LoggerInterface as LoggerInterface;
 

class UserCreation extends Command
{

    const AREA_CODE_LOCK_FILE = 'klevu_areacode.lock';
	
	protected $appState;
	
	protected $_directoryList;
	
	protected $_logger;
	
	protected $_storeInterface;
	
	protected $_api;
	
	protected $_config;
	
	public function __construct(
		AppState $appState,
        StoreManagerInterface $storeInterface,
		DirectoryList $directoryList,
		LoggerInterface $logger,
		Api $api,
		Config $config
	)
    {
		$this->appState = $appState;
		$this->_storeInterface = $storeInterface;
		$this->_directoryList = $directoryList;
		$this->_logger = $logger;
		$this->_api = $api;
		$this->_config = $config;
        parent::__construct();
    }
	
	protected function configure()
    {
        $this->setName('klevu:create')
                ->setDescription('Create user and webstore for klevu')
                ->setDefinition($this->getInputList());
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$logDir = $this->_directoryList->getPath(DirectoryList::VAR_DIR);
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
        try {
            if ($input->hasParameterOption('--userstore')) {
				$email = "test".rand()."@klevudemo.com";
				$userPlan = "premium";
				$partnerAccount = false;
				$url = "test".rand()."@klevudemo.com";
				$merchantEmail = "test".rand()."@klevudemo.com";
				$contactNo = "123456";
				$password = "123456";
                $result = $this->_api->createUser($email, $password, $userPlan, $partnerAccount, $url, $merchantEmail, $contactNo);
				$store = $this->_storeInterface->getStore(1);
				$result = $this->_api->createWebstore($result["customer_id"], $store);
				$config = $this->_config;
				if ($result["success"]) {
					$config->setJsApiKey($result["webstore"]->getJsApiKey(), $store);
					$config->setRestApiKey($result["webstore"]->getRestApiKey(), $store);
					$config->setHostname($result["webstore"]->getHostedOn(), $store);
					$config->setCloudSearchUrl($result['webstore']->getCloudSearchUrl(), $store);
					$config->setAnalyticsUrl($result['webstore']->getAnalyticsUrl(), $store);
					$config->setJsUrl($result['webstore']->getJsUrl(), $store);
					$config->setRestHostname($result['webstore']->getRestHostname(), $store);
					$config->setTiresUrl($result['webstore']->getTiresUrl(), $store);
					$config->resetConfig();
				} 
            }
            
            if ($input->hasParameterOption('--user')) {
				
                $output->writeln('<info>User created successfuly</info>');
				
            }
			
        } catch (LocalizedException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        } catch (Exception $e) {
            $output->writeln('<error>Not able to update</error>');
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
        
    }

    public function getInputList()
    {
        $inputList = [];

        $inputList[] = new InputOption(
            'user',
            null,
            InputOption::VALUE_OPTIONAL,
            'User creation has been done',
            'user'
        );
		
		$inputList[] = new InputOption(
            'userstore',
            null,
            InputOption::VALUE_OPTIONAL,
            'User creation has been done',
            'user'
        );
        return $inputList;
    }
}
