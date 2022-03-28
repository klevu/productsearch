<?php

namespace Klevu\Search\Service\ThemeV2\InteractiveOptionsProvider;

use Klevu\FrontendJs\Api\InteractiveOptionsProviderInterface;
use Klevu\Metadata\Api\IsEnabledConditionInterface;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\System\Config\Source\Landingoptions;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class FrontendOptionsProvider implements InteractiveOptionsProviderInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var IsEnabledConditionInterface
     */
    private $isEnabledCondition;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param IsEnabledConditionInterface $isEnabledCondition
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        IsEnabledConditionInterface $isEnabledCondition,
        UrlInterface $urlBuilder,
        RequestInterface $request
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->isEnabledCondition = $isEnabledCondition;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

    /**
     * @param int|null $storeId
     * @return array[]
     */
    public function execute($storeId = null)
    {
        if (!$this->isEnabledCondition->execute($storeId)) {
            return [];
        }

        $landingEnabled = (int)$this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_LANDING_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $landingUrlSlug = $landingEnabled === Landingoptions::KlEVULAND
            ? 'search'
            : 'catalogsearch/result';

        return [
            'url' => [
                'landing' => $this->urlBuilder->getUrl(
                    $landingUrlSlug,
                    ['_secure' => $this->request->isSecure()]
                ),
            ],
        ];
    }
}
