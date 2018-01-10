<?php
namespace Klevu\Search\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use Klevu\Search\Model\Product\Sync;
use Klevu\Search\Model\Order\Sync as Order;
use Klevu\Content\Model\Content;
use Magento\Framework\App\Filesystem\DirectoryList;

class SyncCommand extends Command
{
    const LOCK_FILE = 'klevu_running_index.lock';

    protected function configure()
    {
        $this->setName('klevu:syncdata')
                ->setDescription('Sync product and content Data With klevu.')
                ->setDefinition($this->getInputList());
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directoryList = ObjectManager::getInstance()->get(DirectoryList::class);
        $logDir = $directoryList->getPath(DirectoryList::VAR_DIR);
        $dir = $logDir."/".self::LOCK_FILE;
        if (file_exists($dir)) {
            $output->writeln('<info>Klevu indexing process is in running state</info>');
            return;
        }

        fopen($dir, 'w');
            
        try {
            $state = ObjectManager::getInstance()->get('\Magento\Framework\App\State');
            $state->setAreaCode('frontend');

            //Sync Data
            $sync = ObjectManager::getInstance()->get(Sync::class);
                
            if ($input->hasParameterOption('--alldata')) {
                $sync->markAllProductsForUpdate();
            }

            $sync->run();
            
            // sync cms data
            $sync = ObjectManager::getInstance()->get(Content::class);
            $sync->run();
            
            // sync order data
            $syncOrder = ObjectManager::getInstance()->get(Order::class);
            $syncOrder->run();
            
			$klevusession = \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Model\Session');
			
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

        if (file_exists($dir)) {
            unlink($dir);
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
