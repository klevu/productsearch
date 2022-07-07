<?php

namespace Klevu\Search\Block\Adminhtml\Form;

use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Helper\Config as Klevu_HelperConfig;
use Klevu\Search\Model\Order\OrdersWithSameIPCollection;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\ModuleList;
use Klevu\Search\Helper\Backend as Klevu_HelperBackend;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Asset\RepositoryFactory as AssetRepositoryFactory;
use Magento\Framework\View\Helper\Js as FrameworkJS;

/**
 * @deprecated has been split into multiple blocks and templates
 * see etc/adminhtml/system/search.xml <group id="information>"
 */
class Information extends Fieldset
{
    protected $_template = 'klevu/search/form/information.phtml';
    /**
     * @var Klevu_HelperConfig
     */
    protected $_searchHelperConfig;
    /**
     * @var DirectoryList
     */
    protected $_directoryList;
    /**
     * @var ModuleList
     */
    protected $_moduleList;
    /**
     * @var Context
     */
    protected $_context;
    /**
     * @var Filesystem
     */
    protected $_fileSystem;
    /**
     * @var Klevu_HelperBackend
     */
    protected $_searchHelperBackend;
    /**
     * @var OrdersWithSameIPCollection|null
     */
    private $ordersWithSameIPCollection;
    /**
     * @var GetFeaturesInterface
     */
    private $getFeatures;
    /**
     * @var AssetRepositoryFactory
     */
    private $assetRepositoryFactory;

    /**
     * @note Unused arguments retained for backwards compatibility
     */
    public function __construct(
        Context $context,
        Session $authSession,
        FrameworkJS $jsHelper,
        Klevu_HelperConfig $config,
        Klevu_HelperBackend $searchHelperBackend,
        DirectoryList $directoryList,
        ModuleList $moduleList,
        Filesystem $fileSystem,
        array $data = [],
        OrdersWithSameIPCollection $ordersWithSameIPCollection = null,
        GetFeaturesInterface $getFeatures = null,
        AssetRepositoryFactory $assetRepositoryFactory = null
    ) {
        $this->_searchHelperConfig = $config;
        $this->_directoryList = $directoryList;
        $this->_moduleList = $moduleList;
        $this->_fileSystem = $fileSystem;
        $this->_searchHelperBackend = $searchHelperBackend;
        $this->_context = $context;
        $this->ordersWithSameIPCollection = $ordersWithSameIPCollection ?: ObjectManager::getInstance()->get(OrdersWithSameIPCollection::class);
        $this->getFeatures = $getFeatures ?: ObjectManager::getInstance()->get(GetFeaturesInterface::class);
        $this->assetRepositoryFactory = $assetRepositoryFactory ?: ObjectManager::getInstance()->get(AssetRepositoryFactory::class);
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     * @deprecated has been split into multiple blocks and templates
     * see etc/adminhtml/system/search.xml group id="information"
     */
    public function render(AbstractElement $element)
    {
        return '';
    }

    /**
     * @return void
     */
    public function getFeatures()
    {
        $this->getFeatures->execute();
    }
}
