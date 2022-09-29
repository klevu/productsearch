<?php

namespace Klevu\Search\Console\Command;

use Klevu\Search\Helper\Api;
use Klevu\Search\Helper\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated Use Klevu Merchant Center (https://box.klevu.com/analytics) to create stores and manage your users.
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
    ) {
        $this->appState = $appState;
        $this->_storeInterface = $storeInterface;
        $this->_directoryList = $directoryList;
        $this->_logger = $logger;
        $this->_api = $api;
        $this->_config = $config;
        $this->descriptorHelper = $descriptorHelper;
        parent::__construct();
    }

    /**
     * @return void
     * @deprecated Use Klevu Merchant Center (https://box.klevu.com/analytics) to create stores and manage your users.
     */
    protected function configure()
    {
        $description = 'This feature has been depreciated from CLI.' .
            PHP_EOL .
            '    ' .
            '<comment>' .
            'Please use Klevu Merchant Center (https://box.klevu.com/analytics) to create stores and manage your users' .
            '</comment>';

        $this->setName('klevu:debug:signup')
            ->setDescription($description)
            ->setDefinition($this->getInputList())
            ->setHelp(
                <<<HELP

$description

HELP
            );
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @deprecated Use Klevu Merchant Center (https://box.klevu.com/analytics) to create stores and manage your users.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>This feature has been depreciated from CLI.</info>');
        $output->writeln(
            '<info>Please use Klevu Merchant Center (https://box.klevu.com/analytics) to create stores and manage your users.</info>'
        );
        $output->writeln('');

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @return array
     * @deprecated Use Klevu Merchant Center (https://box.klevu.com/analytics) to create stores and manage your users.
     */
    public function getInputList()
    {
        return [];
    }
}
