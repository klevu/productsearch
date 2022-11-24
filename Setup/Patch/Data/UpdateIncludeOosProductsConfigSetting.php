<?php

namespace Klevu\Search\Setup\Patch\Data;

use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\CatalogInventory\Model\Configuration as CatalogInventoryConfiguration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateIncludeOosProductsConfigSetting implements DataPatchInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ConfigWriterInterface
     */
    private $configWriter;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigWriterInterface $configWriter
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigWriterInterface $configWriter
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    /**
     * @return string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return UpdateIncludeOosProductsConfigSetting
     */
    public function apply()
    {
        $showOutOfStockProducts = $this->scopeConfig->isSetFlag(
            CatalogInventoryConfiguration::XML_PATH_SHOW_OUT_OF_STOCK,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        if ($showOutOfStockProducts) {
            $this->configWriter->save(
                ConfigHelper::XML_PATH_INCLUDE_OOS_PRODUCTS,
                1,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }

        return $this;
    }
}
