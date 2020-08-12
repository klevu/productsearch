<?php

namespace Klevu\Search\Console\Command;

use Exception;
use Klevu\Search\Helper\Api\Proxy as Api;
use Klevu\Search\Helper\Config as Config;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Psr\Log\LoggerInterface as LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UserCreation
 * @package Klevu\Search\Console\Command
 */
class UserCreation extends Command
{

    const AREA_CODE_LOCK_FILE = 'klevu_areacode.lock';

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeInterface;

    /**
     * @var Api
     */
    protected $_api;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var DescriptorHelper
     */
    protected $descriptorHelper;

    /**
     * UserCreation constructor.
     * @param AppState $appState
     * @param StoreManagerInterface $storeInterface
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param Api $api
     * @param Config $config
     * @param DescriptorHelper $descriptorHelper
     */
    public function __construct(
        AppState $appState,
        StoreManagerInterface $storeInterface,
        DirectoryList $directoryList,
        LoggerInterface $logger,
        Api $api,
        Config $config,
        DescriptorHelper $descriptorHelper
    )
    {
        $this->appState = $appState;
        $this->_storeInterface = $storeInterface;
        $this->_directoryList = $directoryList;
        $this->_logger = $logger;
        $this->_api = $api;
        $this->_config = $config;
        $this->descriptorHelper = $descriptorHelper;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('klevu:debug:signup')
            ->setDescription('
    For debug purposes only. Do not use on your Production stores.
    Use this command to create a new user within the remote Klevu system, retrieve API credentials and
    associate them with your default Magento store (with ID 1).
    Note: This will overwrite any configuration you have for store ID 1.')
            ->setDefinition($this->getInputList())
            ->setHelp(
                <<<HELP

To create a user and generate Klevu credentials:
    <comment>%command.full_name% --user --userstore --password=123456 --confirmpassword=123456</comment>

HELP
            );
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logDir = $this->_directoryList->getPath(DirectoryList::VAR_DIR);
        $areacodeFile = $logDir . "/" . self::AREA_CODE_LOCK_FILE;
        try {
            if (file_exists($areacodeFile)) {
                unlink($areacodeFile);
            }
            $this->appState->setAreaCode('frontend');
        } catch (\Exception $e) {
            fopen($areacodeFile, 'w');
            $this->_logger->critical($e->getMessage());
            throw $e;
        }
        $returnValue = Cli::RETURN_FAILURE;
        try {
            if (!$input->hasParameterOption('--user') && !$input->hasParameterOption('--userstore')) {
                $this->command = $this->getApplication()->get($this->getName());
                if (null === $this->descriptorHelper) {
                    $this->descriptorHelper = new DescriptorHelper();
                }
                $this->descriptorHelper->describe($output, $this->command, [
                    'format' => 'txt',
                    'raw_text' => false
                ]);
                return $returnValue;
            }
            $password = $input->getOption('password');
            $confirmpassword = $input->getOption('confirmpassword');

            if ($password !== $confirmpassword || (strlen($password) < 6)) {
                $output->writeln('<comment>Password and Confirm Password must be same and length must be greater than 6.</comment>');
                return $returnValue;
            }

            if ($input->hasParameterOption('--userstore')) {
                $email = "test" . rand() . "@klevudemo.com";
                $userPlan = "premium";
                $partnerAccount = false;
                $url = "test" . rand() . "@klevudemo.com";
                $merchantEmail = "test" . rand() . "@klevudemo.com";
                $contactNo = "123456";

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

                    $output->writeln('<info>Magento store successfully added to Klevu!</info>');
                }
            }
            if ($input->hasParameterOption('--user')) {
                $output->writeln('<info>Klevu Merchant Center user created successfully!</info>');
            }
            return Cli::RETURN_SUCCESS;

        } catch (LocalizedException $e) {
            $output->writeln('<error>LocalizedException: ' . $e->getMessage() . '</error>');
        } catch (Exception $e) {
            $output->writeln('<error>Exception: Not able to create store/user ' . $e->getMessage() . '</error>');
        }

    }

    public function getInputList()
    {
        $inputList = [];

        $inputList[] = new InputOption(
            'user',
            null,
            InputOption::VALUE_OPTIONAL,
            'To create Klevu Merchant Center user'
        );

        $inputList[] = new InputOption(
            'userstore',
            null,
            InputOption::VALUE_OPTIONAL,
            'To add Magento store in Klevu'
        );

        $inputList[] = new InputOption(
            'password',
            null,
            InputOption::VALUE_REQUIRED,
            'The password you wish to use to access Klevu'
        );

        $inputList[] = new InputOption(
            'confirmpassword',
            null,
            InputOption::VALUE_REQUIRED,
            'Confirmation of the password to access Klevu'
        );
        return $inputList;
    }
}

