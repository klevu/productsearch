<?php

namespace Klevu\Search\Plugin\Cron\Model\Config;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\System\Config\Source\Frequency as FrequencySource;
use Magento\Cron\Model\Config as CronConfigModel;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Plugin to modify jobs configuration for cron frequency config_paths, where primary path is set to "custom"
 *  and a secondary field is used to provide administrators granular control
 */
class SetCustomCronFrequencyPlugin
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * SetCustomCronFrequencyPlugin constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param CronConfigModel $subject
     * @param array[] $result
     * @return array[]
     */
    public function afterGetJobs(
        CronConfigModel $subject,
        $result
    ) {
        if (!is_array($result)) {
            return $result;
        }

        $this->processOrderSyncFrequencyConfig($result);

        return $result;
    }

    /**
     * @param array[] $jobsConfig
     */
    private function processOrderSyncFrequencyConfig(array &$jobsConfig)
    {
        if (!isset($jobsConfig['default']['klevu_search_order_sync']['config_path'])) {
            return;
        }

        $configValue = $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_ORDER_SYNC_FREQUENCY,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
        if ($configValue === FrequencySource::CRON_CUSTOM) {
            $jobsConfig['default']['klevu_search_order_sync']['config_path'] = ConfigHelper::XML_PATH_ORDER_SYNC_FREQUENCY_CUSTOM;
        }
    }
}
