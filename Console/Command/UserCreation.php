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
use Klevu\Search\Helper\Api as Api;
use Klevu\Search\Model\Session as Session;
use Klevu\Search\Helper\Config as Config;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
 

class UserCreation extends Command
{

    protected function configure()
    {
        $this->setName('klevu:create')
                ->setDescription('Create user and webstore for klevu')
                ->setDefinition($this->getInputList());
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

            
        try {
            $state = ObjectManager::getInstance()->get('\Magento\Framework\App\State');
            $state->setAreaCode('frontend');

            //Sync Data
            $api = ObjectManager::getInstance()->get(Api::class);
                
            if ($input->hasParameterOption('--userstore')) {
				$email = "test".rand()."@klevudemo.com";
				$userPlan = "premium";
				$partnerAccount = false;
				$url = "test".rand()."@klevudemo.com";
				$merchantEmail = "test".rand()."@klevudemo.com";
				$contactNo = "123456";
				$password = "123456";
                $result = $api->createUser($email, $password, $userPlan, $partnerAccount, $url, $merchantEmail, $contactNo);
				$store = ObjectManager::getInstance()->get(StoreManagerInterface::class)->getStore(1);
				$result = $api->createWebstore($result["customer_id"], $store);
				$config = ObjectManager::getInstance()->get(Config::class);
				if ($result["success"]) {
					$config->setJsApiKey($result["webstore"]->getJsApiKey(), $store);
					$config->setRestApiKey($result["webstore"]->getRestApiKey(), $store);
					$config->setHostname($result["webstore"]->getHostedOn(), $store);
					$config->setCloudSearchUrl($result['webstore']->getCloudSearchUrl(), $store);
					$config->setAnalyticsUrl($result['webstore']->getAnalyticsUrl(), $store);
					$config->setJsUrl($result['webstore']->getJsUrl(), $store);
					$config->setRestHostname($result['webstore']->getRestHostname(), $store);
					$config->setTiresUrl($result['webstore']->getTiresUrl(), $store);
				} 
            }
            
			$klevusession = \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Model\Session');
			
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
        return $inputList;
    }
}
