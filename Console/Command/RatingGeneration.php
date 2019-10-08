<?php
namespace Klevu\Search\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use \Psr\Log\LoggerInterface as LoggerInterface;
use Klevu\Search\Model\Product\MagentoProductActionsInterface\Proxy as MagentoProductActionsInterface;
 

class RatingGeneration extends Command
{
	const AREA_CODE_LOCK_FILE = 'klevu_areacode.lock';
	
	protected $appState;
	
	protected $_directoryList;
	
	protected $_logger;
	
	protected $_storeInterface;
	
	protected $_magentoProductActionsInterface;
	
	public function __construct(
		AppState $appState,
        StoreManagerInterface $storeInterface,
		DirectoryList $directoryList,
		LoggerInterface $logger,
		MagentoProductActionsInterface $magentoProductActionsInterface
	)
    {
		$this->appState = $appState;
		$this->_storeInterface = $storeInterface;
		$this->_directoryList = $directoryList;
		$this->_logger = $logger;
		$this->_magentoProductActionsInterface = $magentoProductActionsInterface;
        parent::__construct();
    }
    protected function configure()
    {
        $this->setName('klevu:rating')
                ->setDescription('Regenerate Klevu images using commandline')
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
            if ($input->hasParameterOption('--regenerate')) {	
                $storeList = $this->_storeInterface->getStores();
                foreach ($storeList as $store) {		
                    $this->_magentoProductActionsInterface->updateProductsRating($store);
                }

            }
            if ($input->hasParameterOption('--regenerate')) {
				
                $output->writeln('<info> Klevu rating regenrated successfuly commandline</info>');
				
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
            'regenerate',
            null,
            InputOption::VALUE_OPTIONAL,
            'Regenerate Product Rating using commandline',
            'regenerate'
        );
        return $inputList;
    }
}
