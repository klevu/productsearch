<?php

namespace Klevu\Search\Plugin\Search\Helper;

use Klevu\Search\Helper\Config as SearchConfigHelper;
use Klevu\Search\Model\System\Config\Source\Landingoptions;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Search\Helper\Data as SearchHelper;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\ScopeInterface;

class DataPlugin
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        RequestInterface $request
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

    /**
     * @param SearchHelper $subject
     * @param string $result
     * @param string $query
     *
     * @return string
     */
    public function afterGetResultUrl(SearchHelper $subject, $result)
    {
        if ($this->isKlevuEnabled() && $this->isKlevuLandingEnabled()) {
            return $this->urlBuilder->getUrl(
                'search',
                [
                    '_query' => [QueryFactory::QUERY_VAR_NAME => $this->request->getParam('query')],
                    '_secure' => $this->request->isSecure()
                ]
            );
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function isKlevuEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            SearchConfigHelper::XML_PATH_EXTENSION_ENABLED,
            ScopeInterface::SCOPE_STORES
        );
    }

    /**
     * @return bool
     */
    private function isKlevuLandingEnabled()
    {
        return Landingoptions::KlEVULAND === (int)$this->scopeConfig->getValue(
                SearchConfigHelper::XML_PATH_LANDING_ENABLED,
                ScopeInterface::SCOPE_STORES
            );
    }
}
