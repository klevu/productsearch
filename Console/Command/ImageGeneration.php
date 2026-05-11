<?php

namespace Klevu\Search\Console\Command;

use Exception;
use Klevu\Search\Model\Context\Proxy as Klevu_Context;
use Magento\Catalog\Model\Product as Product_Model;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as Magento_CollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface as LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImageGeneration
 * @package Klevu\Search\Console\Command
 *
 * Usage: This class contains product image processing on stores level. This can be set as a external job if website imports/manages product images from 3rd party system.
 * Make sure to run the any of the sync command after running this command in order to reflect the product images at Klevu.
 */
class ImageGeneration extends Command
{
    const AREA_CODE_LOCK_FILE = 'klevu_areacode.lock';

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var Magento_CollectionFactory
     */
    protected $_magentoCollectionFactory;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * @var Klevu_Context
     */
    protected $_klevuContext;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    protected $imageHelper;

    protected $startTime;

    protected $totalExecutionTime;

    /**
     * @var DescriptorHelper
     */
    protected $descriptorHelper;

    /**
     * ImageGeneration constructor.
     * @param AppState $appState
     * @param Magento_CollectionFactory $magentoCollectionFactory
     * @param Klevu_Context $klevuContext
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param DescriptorHelper $descriptorHelper
     */
    public function __construct(
        AppState $appState,
        Magento_CollectionFactory $magentoCollectionFactory,
        Klevu_Context $klevuContext,
        DirectoryList $directoryList,
        LoggerInterface $logger,
        DescriptorHelper $descriptorHelper
    )
    {
        $this->appState = $appState;
        $this->_magentoCollectionFactory = $magentoCollectionFactory;
        $this->_klevuContext = $klevuContext;
        $this->_directoryList = $directoryList;
        $this->_logger = $logger;
        $this->descriptorHelper = $descriptorHelper;
        parent::__construct();
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
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
        } catch (Exception $e) {
            fopen($areacodeFile, 'w');
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
            $collection->addAttributeToSelect(array('id', 'image'));

            $progress = new ProgressBar($output, count($collection));
            $output->writeln('');
            $progress->setFormat("%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s% \t| <info>%message%</info>");

            if ($input->hasParameterOption('--regenerate')) {
                $this->regenerateImage($progress, $collection);
                $output->writeln('');
                $output->writeln('<info>Image regeneration successfully completed in ' . gmdate('H:i:s', round($this->totalExecutionTime)) . '</info>');
                $returnValue = Cli::RETURN_SUCCESS;

            } elseif ($input->hasParameterOption('--regenerateall')) {
                $this->regenerateImage($progress, $collection, true);
                $output->writeln('');
                $output->writeln('<info>Image regeneration completed for all the products ' . gmdate('H:i:s', round($this->totalExecutionTime)) . '</info>');
                $returnValue = Cli::RETURN_SUCCESS;
            } else {
                $output->writeln('<error>No option provided. Specify --regenerate or --regenerateall option to regenerate the product images</error>');
            }
            $output->writeln('');
            $output->writeln('<comment>To sync the latest image changes with Klevu, run the klevu:syncdata or klevu:syncstore:storecode command.</comment>');
            $output->writeln('<comment>You can skip this step if the CRON is already configured</comment>');
        } catch (LocalizedException $e) {
            $output->writeln('');
            $output->writeln('<error>LocalizedException: ' . $e->getMessage() . '</error>');
        } catch (Exception $e) {
            $output->writeln('');
            $output->writeln('<error>Exception: Not able to regenerate images due to ' . $e->getMessage() . '</error>');
        }
        return $returnValue;
    }


    /**
     * @param $product
     * @param bool $force
     */
    private function regenerateImage($progress, $collection, $force = false)
    {
        foreach ($collection as $product) {
            if ($product instanceof Product_Model) {
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



