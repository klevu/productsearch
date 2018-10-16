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
 

class RatingGeneration extends Command
{

    protected function configure()
    {
        $this->setName('klevu:rating')
                ->setDescription('Regenerate Klevu images using commandline')
                ->setDefinition($this->getInputList());
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
            
        try {
            $state = ObjectManager::getInstance()->get('\Magento\Framework\App\State');
            $state->setAreaCode('frontend');
            if ($input->hasParameterOption('--regenrate')) {	
                $image = ObjectManager::getInstance()->get(Image::class);
                $storeList = ObjectManager::getInstance()->get(StoreManagerInterface::class)->getStores();
                foreach ($storeList as $store) {		
                    ObjectManager::getInstance()->get('Klevu\Search\Model\Product\MagentoProductActionsInterface')->updateProductsRating($store);
                }

            }
            if ($input->hasParameterOption('--regenrate')) {
				
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
            'regenrate',
            null,
            InputOption::VALUE_OPTIONAL,
            'Regenrate Product Rating using commandline',
            'regenrate'
        );
        return $inputList;
    }
}
