<?php

namespace Klevu\Search\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Frequency implements OptionSourceInterface {

    const CRON_HOURLY = "0 * * * *";
    const CRON_EVERY_3_HOURS = "0 */3 * * *";
    const CRON_EVERY_6_HOURS = "0 */6 * * *";
    const CRON_EVERY_12_HOURS = "0 */12 * * *";
    const CRON_DAILY = "0 3 * * *";
	const CRON_NEVER = "0 5 31 2 *";

	const CRON_CUSTOM = 'custom';

    /**
     * @var string[]
     */
	private $options = [
	    'Hourly' => self::CRON_HOURLY,
        'Every 3 hours' => self::CRON_EVERY_3_HOURS,
        'Every 6 hours' => self::CRON_EVERY_6_HOURS,
        'Every 12 hours' => self::CRON_EVERY_12_HOURS,
        'Daily' => self::CRON_DAILY,
        'Never' => self::CRON_NEVER,
    ];

    /**
     * Frequency constructor.
     * @param array|null $options
     */
	public function __construct(array $options = null)
    {
	    if (null !== $options) {
	        $this->options = [];
	        array_walk($options, function ($crontab, $label) {
	            if ($crontab) {
                    $this->options[(string)$label] = (string)$crontab;
                }
            });
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        return array_map(
            static function ($value, $label) {
                return [
                    'value' => $value,
                    'label' => __($label),
                ];
            },
            array_values($this->options),
            array_keys($this->options)
        );
    }
}
