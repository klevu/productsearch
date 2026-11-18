<?php

namespace Klevu\Search\Model\Config\Backend;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Trigger as KlevuModelTrigger;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Trigger extends Value
{
    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null
     */
    private $configResource;

    /**
     * @var searchHelper
     */
    private $searchHelperData;
    /**
     * @var KlevuModelTrigger
     */
    private $klevuModelTrigger;

    /**
     * Trigger constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param SearchHelper $searchHelperData
     * @param KlevuModelTrigger $klevuModelTrigger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SearchHelper $searchHelperData,
        KlevuModelTrigger $klevuModelTrigger,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = [],
        ?\Magento\Config\Model\ResourceModel\Config\Data $configResource = null
    ) {
        $this->configResource = $configResource ?: (
            $resource instanceof \Magento\Framework\Model\ResourceModel\Db\AbstractDb ? $resource : null
        );
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->searchHelperData = $searchHelperData;
        $this->klevuModelTrigger = $klevuModelTrigger;
    }

    /**
     * Set after commit callback
     *
     * @return Trigger
     * @throws LocalizedException
     *
     * @deprecated triggers added via mview
     * @see /etc/mview.xml & /etc/indexer.xml
     */
    public function afterSave()
    {
        $this->getConfigResource()->addCommitCallback([$this, 'processValue']);

        return parent::afterSave();
    }

    /**
     * Process towards activate or drop trigger
     *
     * @return void
     * @deprecated triggers added via mview
     * @see /etc/mview.xml & /etc/indexer.xml
     *
     */
    public function processValue()
    {
        try {
            if ((bool)$this->getValue() !== (bool)$this->getOldValue()) {
                if ((bool)$this->getValue()) {
                    /** @var KlevuModelTrigger */
                    $this->klevuModelTrigger->activateTrigger();
                } else {
                    $this->klevuModelTrigger->dropTriggerIfFoundExist();
                }
            }
        } catch (\Exception $e) {
            $this->searchHelperData->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf(
                    "Exception thrown while SQL trigger: %s::%s - %s",
                    __CLASS__,
                    __METHOD__,
                    $e->getMessage()
                )
            );
        }
    }
    /**
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    private function getConfigResource()
    {
        if ($this->configResource === null) {
            $this->configResource = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Config\Model\ResourceModel\Config\Data::class);
        }

        return $this->configResource;
    }
}
