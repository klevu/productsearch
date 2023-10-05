<?php

namespace Klevu\Search\Console\Command;

use Exception;
use Klevu\Search\Helper\Image as ImageHelper;
use Klevu\Search\Model\Context\Proxy as KlevuContext;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\DriverInterface as FilesystemDriverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImageGeneration
 *
 * Usage: This class contains product image processing on stores level.
 * This can be set as a external job if website imports/manages product images from 3rd party system.
 * Make sure to run the any of the sync command after running this command in order to reflect
 *  the product images at Klevu.
 */
class ImageGeneration extends Command
{
    const AREA_CODE_LOCK_FILE = 'klevu_areacode.lock';

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var ProductCollectionFactory
     */
    protected $_magentoCollectionFactory;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * @var KlevuContext
     */
    protected $_klevuContext;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ImageHelper|null
     */
    protected $imageHelper;

    /**
     * @var float|null
     */
    protected $startTime;

    /**
     * @var float|null
     */
    protected $totalExecutionTime;

    /**
     * @var DescriptorHelper
     */
    protected $descriptorHelper;

    /**
     * @var FilesystemDriverInterface
     */
    private $fileDriver;

    /**
     * @var \Symfony\Component\Console\Command\Command|null
     */
    protected $command = null;

    /**
     * ImageGeneration constructor.
     * @param AppState $appState
     * @param ProductCollectionFactory $magentoCollectionFactory
     * @param KlevuContext $klevuContext
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param DescriptorHelper $descriptorHelper
     * @param FilesystemDriverInterface|null $fileDriver
     */
    public function __construct(
        AppState $appState,
        ProductCollectionFactory $magentoCollectionFactory,
        KlevuContext $klevuContext,
        DirectoryList $directoryList,
        LoggerInterface $logger,
        DescriptorHelper $descriptorHelper,
        FilesystemDriverInterface $fileDriver = null
    ) {
        $this->appState = $appState;
        $this->_magentoCollectionFactory = $magentoCollectionFactory;
        $this->_klevuContext = $klevuContext;
        $this->_directoryList = $directoryList;
        $this->_logger = $logger;
        $this->descriptorHelper = $descriptorHelper;
        $this->fileDriver = $fileDriver ?: ObjectManager::getInstance()->get(FileDriver::class);

        parent::__construct();
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        // phpcs:disable Magento2.SQL.RawQuery.FoundRawSql
        $this->setName('klevu:images')
            ->setDescription('
    Klevu maintains its own copy of your product images in the pub/media/klevu_images directory.
    Sometimes these images can become out of sync and you can use this command to regenerate them.
    This command will not impact your native Magento imagery.')
            ->setDefinition($this->getInputList())
            ->setHelp(
                <<<HELP

Create product images not found in the klevu_images directory:
    <comment>%command.full_name% --regenerate</comment>

Regenerate images for all products:
    <comment>%command.full_name% --regenerateall</comment>

HELP
            );
        // phpcs:enable Magento2.SQL.RawQuery.FoundRawSql
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
            if ($this->fileDriver->isExists($areacodeFile)) {
                $this->fileDriver->deleteFile($areacodeFile);
            }
            $this->appState->setAreaCode('frontend');
        } catch (Exception $e) {
            $this->fileDriver->fileOpen($areacodeFile, 'w');
            $this->_logger->critical($e->getMessage());
            throw $e;
        }

        $returnValue = Cli::RETURN_FAILURE;
        $this->startTime = microtime(true);
        try {
            $this->imageHelper = $this->_klevuContext->getHelperManager()->getImageHelper();

            //If no option provided then we will show help section
            if (!$input->hasParameterOption('--regenerate') && !$input->hasParameterOption('--regenerateall')) {
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

            $output->writeln('=== Starting process for image regeneration === ');
            //Product collection load, limited to
            $collection = $this->_magentoCollectionFactory->create();
            $collection->addAttributeToSelect(['id', 'image']);

            $progress = new ProgressBar($output, count($collection));
            $output->writeln('');
            $progress->setFormat(
                '%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s% \t| <info>%message%</info>'
            );

            if ($input->hasParameterOption('--regenerate')) {
                $this->regenerateImage($progress, $collection);
                $output->writeln('');
                $output->writeln(
                    sprintf(
                        '<info>Image regeneration successfully completed in %s</info>',
                        gmdate('H:i:s', round($this->totalExecutionTime))
                    )
                );
                $returnValue = Cli::RETURN_SUCCESS;

            } elseif ($input->hasParameterOption('--regenerateall')) {
                $this->regenerateImage($progress, $collection, true);
                $output->writeln('');
                $output->writeln(
                    sprintf(
                        '<info>Image regeneration completed for all the products %s</info>',
                        gmdate('H:i:s', round($this->totalExecutionTime))
                    )
                );
                $returnValue = Cli::RETURN_SUCCESS;
            } else {
                $output->writeln(
                    '<error>No option provided. Specify --regenerate or --regenerateall option to regenerate '
                    . 'the product images</error>'
                );
            }
            $output->writeln('');
            $output->writeln(
                '<comment>To sync the latest image changes with Klevu, run the klevu:syncdata or '
                . 'klevu:syncstore:storecode command.</comment>'
            );
            $output->writeln('<comment>You can skip this step if the CRON is already configured</comment>');
        } catch (LocalizedException $e) {
            $output->writeln('');
            $output->writeln('<error>LocalizedException: ' . $e->getMessage() . '</error>');
        } catch (Exception $e) {
            $output->writeln('');
            $output->writeln(
                sprintf(
                    '<error>Exception: Not able to regenerate images due to %s</error>',
                    $e->getMessage()
                )
            );
        }
        return $returnValue;
    }

    /**
     * @param ProgressBar $progress
     * @param ProductCollection $collection
     * @param bool $force
     * @return void
     */
    private function regenerateImage($progress, $collection, $force = false)
    {
        foreach ($collection as $product) {
            if ($product instanceof ProductModel) {
                $progress->setMessage($product->getImage() . ' ');

                $image = $product->getImage();
                if (($image != "no_selection") && (!empty($image))) {
                    if ($force) {
                        $this->imageHelper->generateProductImagesForcefully($product->getImage());
                    } else {
                        $this->imageHelper->getImagePath($product->getImage());
                    }
                }
                $progress->advance();
            }
        }
        $this->totalExecutionTime = microtime(true) - $this->startTime;
    }

    /**
     * @return InputOption[]
     */
    public function getInputList()
    {
        $inputList = [];

        $inputList[] = new InputOption(
            'regenerate',
            null,
            InputOption::VALUE_OPTIONAL,
            'Create product images not found in the klevu_images directory'
        );

        $inputList[] = new InputOption(
            'regenerateall',
            null,
            InputOption::VALUE_OPTIONAL,
            'Regenerate images for all products'
        );
        return $inputList;
    }
}
