<?php


namespace Klevu\Search\Plugin\Admin;


use Klevu\Search\Model\Product\MagentoProductActionsInterface;

/**
 * Class UrlRewriteSavePlugin responsible for marking product for next sync
 * @package Klevu\Search\Plugin\Admin
 */
class UrlRewriteSavePlugin
{
    /**
     * UrlRewriteSavePlugin constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param MagentoProductActionsInterface $magentoProductActions
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        MagentoProductActionsInterface $magentoProductActions
    )
    {
        $this->request = $request;
        $this->magentoProductActions = $magentoProductActions;
    }

    /**
     * @param \Magento\UrlRewrite\Controller\Adminhtml\Url\Rewrite\Save $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterExecute(
        \Magento\UrlRewrite\Controller\Adminhtml\Url\Rewrite\Save $subject,
        $result
    )
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

