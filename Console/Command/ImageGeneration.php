<?php
namespace Klevu\Search\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use Klevu\Search\Helper\Image as Image;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
 

class ImageGeneration extends Command
{

    protected function configure()
    {
        $this->setName('klevu:images')
                ->setDescription('Regenerate Klevu images using commandline')
                ->setDefinition($this->getInputList());
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
            
        try {
            $state = ObjectManager::getInstance()->get('\Magento\Framework\App\State');
            $state->setAreaCode('frontend');

            //Sync Data
            $image = ObjectManager::getInstance()->get(Image::class);
                
            if ($input->hasParameterOption('--regenerate')) {
				$collections = ObjectManager::getInstance()->get('Magento\Catalog\Model\Product')->getCollection(); 
				$objectManager = ObjectManager::getInstance();
				foreach($collections as $collection){
					$productModel = $objectManager->create('Magento\Catalog\Model\Product');
					$product = $productModel->load($collection->getId());
					$image->getImagePath($product->getImage());
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