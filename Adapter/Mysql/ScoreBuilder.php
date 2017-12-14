<?php
/**
 * Klevu override of the score builder for use on preserve layout
 */
namespace Klevu\Search\Adapter\Mysql;

use Magento\Framework\Registry as MagentoRegistry;
/**
 * Class for generating sql condition for calculating store manager
 */

class ScoreBuilder extends \Magento\Framework\Search\Adapter\Mysql\ScoreBuilder
{
    private $magentoRegistry;

    public function __construct(
        MagentoRegistry $magentoRegistry
    ) {
        $this->magentoRegistry = $magentoRegistry;
        if (is_callable('parent::__construct')) {
            parent::__construct();
        }
    }

    /**
     * Get generated sql condition for global score
     *
     * @return string
     */
    public function build()
    {
        $scoreAlias = parent::getScoreAlias();
        $sessionOrder = $this->magentoRegistry->registry('search_ids');
        if(is_array($sessionOrder)) return "FIELD(search_index.entity_id,".implode(",",array_reverse($sessionOrder)).") AS {$scoreAlias}";
        return parent::build();
    }

}