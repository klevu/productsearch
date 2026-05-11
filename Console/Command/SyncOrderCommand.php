<?php

namespace Klevu\Search\Console\Command;

use Klevu\Search\Model\Order\Sync as OrderSyncModel;
use Klevu\Search\Model\Order\SyncFactory as OrderSyncModelFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncOrderCommand extends Command
{
    const COMMAND_NAME = 'klevu:syncorder';

    const OPTION_STORE_CODE = 'store';

    const AREA_CODE_LOCK_FILE = 'klevu_areacode.ordersync.lock';

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderSyncModelFactory
     */
    private $orderSyncModelFactory;

    /**
     * @var string|null
     */
    private $klevuLoggerFQCN;

    /**
     * @param AppState $appState
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param OrderSyncModelFactory $orderSyncModelFactory
     * @param string|null $klevuLoggerFQCN
     * @param string|null $name
     */
    public function __construct(
        AppState $appState,
        DirectoryList $directoryList,
        LoggerInterface $logger,
        OrderSyncModelFactory $orderSyncModelFactory,
        $klevuLoggerFQCN = null,
        $name = null
    ) {
        parent::__construct($name);

        $this->appState = $appState;
        $this->directoryList = $directoryList;
        $this->logger = $logger;
        $this->orderSyncModelFactory = $orderSyncModelFactory;
        if (is_string($klevuLoggerFQCN)) {
            $this->klevuLoggerFQCN = $klevuLoggerFQCN;
        }
    }

    /**
     * {@inheritdoc}
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this->setName(static::COMMAND_NAME);
        $this->setDescription('Trigger a synchronisation of outstanding order data with Klevu');
        $this->addOption(
            static::OPTION_STORE_CODE,
            null,
            InputOption::VALUE_OPTIONAL + InputOption::VALUE_IS_ARRAY,
            'Store Code(s) to sync. Omit to process all stores with order sync enabled.'
        );
        $this->setHelp('
Send order records which have not yet been synced with Klevu for all enabled stores
    <comment>%command.full_name%</comment>
    
Send order records which have not yet been synced with Klevu for store with code "default"
    <comment>%command.full_name% --store default</comment>
    
Send order records which have not yet been synced with Klevu for stores with codes "default" and "new_store"
    <comment>%command.full_name% --store default --store new_store</comment>
        ');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // See comments against methods for background. Ref: KS-7853
        $this->initLogger();

        $verbosity = $output->getVerbosity();
        if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            $startTime = time();
            $startMemory = memory_get_usage(true);
        }

        try {
            $areaCodeLockFile = $this->directoryList->getPath(DirectoryList::VAR_DIR)
                . DIRECTORY_SEPARATOR
                . static::AREA_CODE_LOCK_FILE;
            if (file_exists($areaCodeLockFile)) {
                unlink($areaCodeLockFile);
            }

            $this->appState->setAreaCode('frontend');
        } catch (LocalizedException $e) {
            $existingAreaCode = $this->appState->getAreaCode();

            if ('frontend' !== $existingAreaCode) {
                touch($areaCodeLockFile);
                $this->logger->error($e->getMessage(), [
                    'method' => __METHOD__,
                    'existingAreaCode' => $existingAreaCode,
                ]);
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

                return Cli::RETURN_FAILURE;
            }
        }

        $return = Cli::RETURN_SUCCESS;

        $storeCodes = $this->getStoreCodes($input);
        if ($verbosity) {
            $output->writeln((string)__(
                'Starting orders sync for %1',
                $storeCodes ? implode(', ', $storeCodes) : __('all stores')
            ));
        }

        try {
            /** @var OrderSyncModel $orderSyncModel */
            $orderSyncModel = $this->orderSyncModelFactory->create();
            $orderSyncModel->setStoreCodesToRun($storeCodes);
            $orderSyncModel->run();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['originalException' => $e]);
            $return = Cli::RETURN_FAILURE;

            if ($verbosity) {
                $output->writeln(sprintf(
                    '<error>%s</error>',
                    __('Encountered error: %1', $e->getMessage())
                ));
            }
        }

        if ($verbosity) {
            $output->writeln((string)__(
                'Orders sync completed for %1 %2',
                $storeCodes ? implode(', ', $storeCodes) : (string)__('all stores'),
                __($return === Cli::RETURN_SUCCESS ? 'successfully' : 'with errors - please check logs for more details')
            ));

            if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
                $endTime = time();
                $endMemory = memory_get_usage(true);
                $peakMemory = memory_get_peak_usage(true);

                $output->writeln(sprintf(
                    '<info>%s</info>',
                    __(
                        'Operation completed in %1 seconds using %2 net memory (%3 peak)',
                        number_format($endTime - $startTime),
                        $this->convertBytesToHumanReadable($endMemory - $startMemory),
                        $this->convertBytesToHumanReadable($peakMemory)
                    )
                ));
            }
        }

        return $return;
    }

    /**
     * @param InputInterface $input
     * @return string[]|null
     */
    private function getStoreCodes(InputInterface $input)
    {
        $inputValues = $input->getOption(static::OPTION_STORE_CODE);
        if (is_string($inputValues)) {
            $inputValues = [$inputValues];
        }

        return ($inputValues)
            ? array_unique(array_filter(array_map('trim', $inputValues)))
            : null;
    }

    /**
     * @param float $bytes
     * @return string
     */
    private function convertBytesToHumanReadable($bytes)
    {
        $suffixes = ['B', 'KiB', 'MiB', 'GiB'];
        $returnValue = $bytes;
        while (count($suffixes) > 1 && ($returnValue /= 1024) > 1024) {
            array_shift($suffixes);
        }

        return number_format($returnValue) . ' ' . array_shift($suffixes);
    }

    /**
     * Check that the logger property is of the expected class and, if not, create using OM
     *
     * In order to support updates from 2.3.x to 2.4.x, which introduced the logger module,
     *  we can't inject the actual logger through DI as all CLI commands are instantiated
     *  by bin/magento. This prevents setup:upgrade running and enabling the logger module
     *  because the logger module isn't already enabled.
     * As such, we pass an FQCN for the desired logger class and then check that it matches
     *  at the start of any method utilising it
     * We avoid temporal coupling by falling back to the standard LoggerInterface in the
     *  constructor
     *
     * @return void
     */
    private function initLogger()
    {
        $objectManager = ObjectManager::getInstance();
        if ($this->klevuLoggerFQCN && !($this->logger instanceof $this->klevuLoggerFQCN)) {
            $this->logger = $objectManager->get($this->klevuLoggerFQCN);
        }

        if (!($this->logger instanceof LoggerInterface)) {
            $this->logger = $objectManager->get(LoggerInterface::class);
        }
    }
}
