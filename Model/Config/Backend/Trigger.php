<?php

namespace Klevu\Search\Model\Config\Backend;

use Klevu\Logger\Constants as LoggerConstants;

/**
 * Class Trigger
 * @package Klevu\Search\Model\Config\Backend
 */
class Trigger extends \Magento\Framework\App\Config\Value
{

    /**
     * @var searchHelperData
     */
    private $searchHelperData;

    /**
     * @var klevuModelTrigger
     */
    private $klevuModelTrigger;

    /**
     * Trigger constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Klevu\Search\Helper\Data $searchHelperData
     * @param \Klevu\Search\Model\Trigger $klevuModelTrigger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Klevu\Search\Helper\Data $searchHelperData,
        \Klevu\Search\Model\Trigger $klevuModelTrigger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->searchHelperData = $searchHelperData;
        $this->klevuModelTrigger = $klevuModelTrigger;
    }


    /**
     * Set after commit callback
     *
     * @return Trigger
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSave()
    {
        $this->_getResource()->addCommitCallback([$this, 'processValue']);
        return parent::afterSave();
    }

    /**
     * Process towards activate or drop trigger
     *
     * @return void
     */
    public function processValue()
    {
        try {
            if ((bool)$this->getValue() != (bool)$this->getOldValue()) {
                if ((bool)$this->getValue()) {
                    /** @var \Klevu\Search\Model\Trigger */
                    $this->klevuModelTrigger->activateTrigger();
                } else {
                    $this->klevuModelTrigger->dropTriggerIfFoundExist();
                }
            }
        } catch (\Exception $e) {
            $this->searchHelperData->log(LoggerConstants::ZEND_LOG_CRIT, sprintf("Exception thrown while SQL trigger: %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
    }
}


