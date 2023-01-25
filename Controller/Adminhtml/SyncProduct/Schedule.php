<?php

namespace Klevu\Search\Controller\Adminhtml\SyncProduct;

use Exception;
use InvalidArgumentException;
use Klevu\Search\Api\Service\Sync\ScheduleSyncInterface;
use Klevu\Search\Exception\MissingSyncEntityIds;
use Klevu\Search\Exception\StoreNotIntegratedException;
use Klevu\Search\Exception\StoreSyncDisabledException;
use Klevu\Search\Service\Sync\Product\Schedule as ScheduleSync;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;

class Schedule extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Klevu_Search::sync_product_grid';

    /**
     * @var ScheduleSync
     */
    private $productSync;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @param Context $context
     * @param ScheduleSyncInterface $productSync
     * @param ProductRepositoryInterface $productRepository
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        Context $context,
        ScheduleSyncInterface $productSync,
        ProductRepositoryInterface $productRepository,
        StoreRepositoryInterface $storeRepository
    ) {
        parent::__construct($context);
        $this->productSync = $productSync;
        $this->productRepository = $productRepository;
        $this->storeRepository = $storeRepository;
    }

    /**
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $request = $this->getRequest();
        $productId = $request->getParam('id');
        $storeId = $request->getParam('store');

        $messageManager = $this->getMessageManager();
        try {
            if (!$storeId || !is_numeric($storeId)) {
                throw new InvalidArgumentException(
                    __('Invalid Store ID Provided.')
                );
            }
            $this->productSync->execute([$productId], (int)$storeId);
            $messageManager->addSuccessMessage(
                $this->generateSuccessMessage($productId, $storeId)
            );
        } catch (MissingSyncEntityIds $exception) {
            $messageManager->addErrorMessage($exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            $messageManager->addErrorMessage($exception->getMessage());
        } catch (NoSuchEntityException $exception) {
            $messageManager->addErrorMessage($exception->getMessage());
        } catch (StoreNotIntegratedException $exception) {
            $messageManager->addErrorMessage($exception->getMessage());
        } catch (StoreSyncDisabledException $exception) {
            $messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->reload();
    }

    /**
     * @return Redirect
     */
    private function reload()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath(
            $this->_redirect->getRefererUrl()
        );

        return $resultRedirect;
    }

    /**
     * @param string $productId
     * @param string $storeId
     *
     * @return Phrase
     */
    private function generateSuccessMessage($productId, $storeId)
    {
        $product = $this->getProductForMessage($productId);
        $store = $this->getStoreForMessage($storeId);

        return __(
            'Product %1 has been added to Sync Schedule in %2',
            $product ? 'SKU (' . $product->getSku() . ')' : ' ID (' . $productId . ')',
            $store ? $store->getName() : ' store ' . $storeId
        );
    }

    /**
     * @param string $productId
     *
     * @return ProductInterface|null
     */
    private function getProductForMessage($productId)
    {
        $ids = explode('-', $productId);
        $id = isset($ids[1]) ? $ids[1] : null;
        $product = null;
        if ($id) {
            try {
                $product = $this->productRepository->getById((int)$id);
                // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            } catch (Exception $exception) {
                // this is fine, display id instead of sku
            }
        }

        return $product;
    }

    /**
     * @param string $storeId
     *
     * @return StoreInterface|null
     */
    private function getStoreForMessage($storeId)
    {
        $store = null;
        try {
            $store = $this->storeRepository->getById((int)$storeId);
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (NoSuchEntityException $e) {
            // this is fine, display id instead of store name
        }

        return $store;
    }
}
