<?php

namespace Klevu\Search\Plugin\Admin;

use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Framework\App\RequestInterface;
use Magento\UrlRewrite\Controller\Adminhtml\Url\Rewrite\Save as UrlRewriteSave;

/**
 * Class UrlRewriteSavePlugin responsible for marking product for next sync
 */
class UrlRewriteSavePlugin
{
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var MagentoProductActionsInterface
     */
    protected $magentoProductActions;

    /**
     * UrlRewriteSavePlugin constructor.
     *
     * @param RequestInterface $request
     * @param MagentoProductActionsInterface $magentoProductActions
     */
    public function __construct(
        RequestInterface $request,
        MagentoProductActionsInterface $magentoProductActions
    ) {
        $this->request = $request;
        $this->magentoProductActions = $magentoProductActions;
    }

    /**
     * @param UrlRewriteSave $subject
     * @param void $result
     *
     * @return void
     */
    public function afterExecute(UrlRewriteSave $subject, $result)
    {
        try {
            $productId = (int)$this->request->getParam('product', 0);
            $storeId = $this->request->getParam('store_id', 0);
            if (!empty($productId)) {
                $this->magentoProductActions->markRecordIntoQueue([$productId], 'products', $storeId);
            }
        } catch (\Exception $e) {
            return $result;
        }

        return $result;
    }
}
