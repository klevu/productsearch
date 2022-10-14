<?php

namespace Klevu\Search\Ui\DataProvider\Product\Form\Modifier\Attributes;

use Klevu\Search\Model\Attribute\Rating as RatingAttribute;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\ArrayManager;

class Rating extends AbstractModifier
{
    const CONFIG_PATH_SINGLE_STORE_MODE = 'general/single_store_mode/enabled';

    /**
     * @var ArrayManager
     */
    private $arrayManager;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ArrayManager $arrayManager
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ArrayManager $arrayManager,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->arrayManager = $arrayManager;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * modifyData
     *
     * @param array $data
     *
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * modifyMeta
     *
     * @param array $meta
     *
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $path = $this->arrayManager->findPath(
            RatingAttribute::ATTRIBUTE_CODE,
            $meta,
            null,
            'children'
        );
        if (!$path) {
            return $meta;
        }

        if (!$this->request->getParam('store') && !$this->isSingleStoreMode()) {
            $meta = $this->arrayManager->set(
                "{$path}/arguments/data/config/visible",
                $meta,
                false
            );
        }

        return $this->arrayManager->set(
            "{$path}/arguments/data/config/disabled",
            $meta,
            true
        );
    }

    /**
     * @return bool
     */
    private function isSingleStoreMode()
    {
        $singleStoreMode = $this->scopeConfig->getValue(
            self::CONFIG_PATH_SINGLE_STORE_MODE
        );

        return (bool)$singleStoreMode;
    }
}
