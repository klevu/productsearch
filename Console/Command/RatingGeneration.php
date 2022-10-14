<?php

namespace Klevu\Search\Console\Command;

use Exception;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RatingGeneration
 * Usage: This class contains product rating processing on stores level.
 * This can be set as a external job if website manages product ratings on 3rd party system.
 * Make sure to run the klevu:syncdata --updatesonly command
 * after running this command in order to reflect the product ratings at Klevu.
 */
class RatingGeneration extends Command
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
     * @var MagentoProductActionsInterface
     */
    protected $_magentoProductActionsInterface;
    /**
     * @var DescriptorHelper
     */
    protected $descriptorHelper;

    /**
     * RatingGeneration constructor.
     *
     * @param AppState $appState
     * @param StoreManagerInterface $storeInterface
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param MagentoProductActionsInterface $magentoProductActionsInterface
     * @param DescriptorHelper $descriptorHelper
     */
    public function __construct(
        AppState $appState,
        StoreManagerInterface $storeInterface,
        DirectoryList $directoryList,
        LoggerInterface $logger,
        MagentoProductActionsInterface $magentoProductActionsInterface,
        DescriptorHelper $descriptorHelper
    ) {
        $this->appState = $appState;
        $this->_storeInterface = $storeInterface;
        $this->_directoryList = $directoryList;
        $this->_logger = $logger;
        $this->_magentoProductActionsInterface = $magentoProductActionsInterface;
        $this->descriptorHelper = $descriptorHelper;
        parent::__construct();
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('klevu:rating')
            ->setDescription('
    Klevu maintains a product attribute `rating` with the average rating per product for each store.
    Sometimes these ratings can become out of sync. Use this command to recalculate the ratings for each product.')
            ->setDefinition($this->getInputList())
            ->setHelp(
                <<<HELP

Recalculate the product ratings for all stores:
    <comment>%command.full_name% --regenerate</comment>

HELP
            );
        parent::configure();
    }

    /**
     * Run the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool|int
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logDir = $this->_directoryList->getPath(DirectoryList::VAR_DIR);
        $areacodeFile = $logDir . "/" . self::AREA_CODE_LOCK_FILE;
        try {
            if (file_exists($areacodeFile)) { //phpcs:ignore
                unlink($areacodeFile); //phpcs:ignore
            }
            $this->appState->setAreaCode('frontend');
        } catch (\Exception $e) {
            fopen($areacodeFile, 'w'); //phpcs:ignore
            $this->_logger->critical($e->getMessage());
            throw $e;
        }

        $returnValue = Cli::RETURN_FAILURE;
        $startTime = microtime(true);
        try {
            if ($input->hasParameterOption('--regenerate')) {
                $output->writeln('=== Starting process for product rating ===');
                $output->writeln('');

                $output->writeln('<info>0. Clearing data in global scope</info>');
                $this->_magentoProductActionsInterface->updateProductsRating(Store::DEFAULT_STORE_ID);
                $output->writeln('<info>   Completed for global scope</info>');
                $output->writeln('');

                $storeList = $this->_storeInterface->getStores();
                foreach ($storeList as $key => $store) {
                    $output->writeln(
                        '<info>' . $key . '. Started for store name "' . $store->getName() . '"</info>'
                    );
                    //Product ratings processing for specific store
                    $this->_magentoProductActionsInterface->updateProductsRating($store);
                    $output->writeln('<info>   Completed for store name "' . $store->getName() . '"</info>');
                    $output->writeln('');
                }
                $resultTime = microtime(true) - $startTime;
                $output->writeln(
                    '<info>Product rating recalculation successfully completed in ' .
                    gmdate('H:i:s', round($resultTime)) .
                    '</info>'
                );
                $output->writeln('');
                $output->writeln(
                    '<comment>' .
                    'To sync the latest rating changes with Klevu, ' .
                    'run the klevu:syncdata or klevu:syncstore:storecode command.' .
                    '</comment>'
                );
                $output->writeln('<comment>You can skip this step if the CRON is already configured</comment>');
                $returnValue = Cli::RETURN_SUCCESS;
            } else {
                $output->writeln(
                    '<error>' .
                    'No option provided. Specify --regenerate option to recalculate the product rating' .
                    '</error>'
                );
            }
        } catch (LocalizedException $e) {
            $output->writeln('<error>LocalizedException: ' . $e->getMessage() . '</error>');
        } catch (Exception $e) {
            $output->writeln('');
            $output->writeln(
                '<error>Exception: Not able to recalculate product rating due to ' .
                $e->getMessage() .
                '</error>'
            );
        }

        return $returnValue;
    }

    /**
     * Get list of options for the command
     *
     * @return array
     */
    public function getInputList()
    {
        $inputList = [];
        $inputList[] = new InputOption(
            'regenerate',
            null,
            InputOption::VALUE_OPTIONAL,
            'Recalculate the product ratings for all stores'
        );

        return $inputList;
    }
}
