<?php
namespace Klevu\Search\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as Magento_CollectionFactory;
use Magento\Framework\App\State as AppState;
use Klevu\Search\Model\Context\Proxy as Klevu_Context;
use \Magento\Catalog\Model\ProductFactory as Product_Factory;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use \Psr\Log\LoggerInterface as LoggerInterface;

class ImageGeneration extends Command
{
	const AREA_CODE_LOCK_FILE = 'klevu_areacode.lock';
	
	protected $appState;
	
	protected $imageHelper;
	
	protected $_magentoCollectionFactory;
	
	protected $_productObject;	
	
	protected $_directoryList;
	
	protected $_klevuContext;
	
	protected $_logger;
	
	public function __construct(
		AppState $appState,
		Magento_CollectionFactory $magentoCollectionFactory,
		Product_Factory $productObject,
		Klevu_Context $klevuContext,
		DirectoryList $directoryList,
		LoggerInterface $logger
	)
    {
		$this->appState = $appState;
		$this->_magentoCollectionFactory = $magentoCollectionFactory;
		$this->_klevuContext = $klevuContext;
		$this->_productObject = $productObject;
		$this->_directoryList = $directoryList;
		$this->_logger = $logger;
        parent::__construct();
    }
	
    protected function configure()
    {
        $this->setName('klevu:images')
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
			$this->imageHelper = $this->_klevuContext->getHelperManager()->getImageHelper();   
            if ($input->hasParameterOption('--regenerate')) {
				$collections = $this->_magentoCollectionFactory->create(); 
				foreach($collections as $collection){
					$product = $this->_productObject->create()->load($collection->getId());
					$this->imageHelper->getImagePath($product->getImage());
				}
            }
            if ($input->hasParameterOption('--regenerate')) {
				
                $output->writeln('<info> Klevu images regenrated successfuly commandline</info>');
				
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
            'Regenerate Klevu images using commandline',
            'regenerate'
        );
        return $inputList;
    }
}