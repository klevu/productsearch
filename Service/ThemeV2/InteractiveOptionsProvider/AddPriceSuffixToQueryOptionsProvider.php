<?php

namespace Klevu\Search\Service\ThemeV2\InteractiveOptionsProvider;

use Klevu\FrontendJs\Api\InteractiveOptionsProviderInterface;
use Klevu\FrontendJs\Api\IsEnabledConditionInterface;

class AddPriceSuffixToQueryOptionsProvider implements InteractiveOptionsProviderInterface
{
    /**
     * @var IsEnabledConditionInterface
     */
    private $isEnabledCondition;

    /**
     * @param IsEnabledConditionInterface $isEnabledCondition
     */
    public function __construct(
        IsEnabledConditionInterface $isEnabledCondition
    ) {
        $this->isEnabledCondition = $isEnabledCondition;
    }

    /**
     * @param int $storeId
     * @return array
     */
    public function execute($storeId = null)
    {
        if (!$this->isEnabledCondition->execute($storeId)) {
            return [];
        }

        return [
            'powerUp' => [
                'quick' => false,
                'landing' => false,
            ],
        ];
    }
}
