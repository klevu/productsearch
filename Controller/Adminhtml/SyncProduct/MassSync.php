<?php

namespace Klevu\Search\Controller\Adminhtml\SyncProduct;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Sync\SyncNowInterface;
use Klevu\Search\Exception\MissingSyncEntityIds;
use Klevu\Search\Exception\StoreNotIntegratedException;
use Klevu\Search\Exception\StoreSyncDisabledException;
use Klevu\Search\Exception\SyncRequestFailedException;
use Klevu\Search\Service\Sync\Product\SyncNow;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;

class MassSync extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Klevu_Search::sync_product_grid';

    /**
     * @var SyncNow
     */
    private $productSync;
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @param Context $context
     * @param SyncNowInterface $productSync
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        Context $context,
        SyncNowInterface $productSync,
        StoreRepositoryInterface $storeRepository
    ) {
        parent::__construct($context);
        $this->productSync = $productSync;
        $this->storeRepository = $storeRepository;
    }

    /**
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $request = $this->getRequest();
        $storeId = $request->getParam('store');
        $selected = $request->getParam('selected');
        $ids = $selected ?: [];

        $messageManager = $this->getMessageManager();
        try {
            if (!$storeId || !is_numeric($storeId)) {
                throw new InvalidArgumentException(
                    __('Invalid Store ID Provided.')
                );
            }
            $this->productSync->execute($ids, (int)$storeId);
            $messageManager->addSuccessMessage(
                $this->generateSuccessMessage($ids, $storeId)
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
        } catch (SyncRequestFailedException $exception) {
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
     * @param array $productIds
     * @param string $storeId
     *
     * @return Phrase
     */
    private function generateSuccessMessage($productIds, $storeId)
    {
        $store = $this->getStoreForMessage($storeId);

        return __(
            'Product sync triggered for %1 products in %2',
            count($productIds),
            $store ? $store->getName() : ' store ' . $storeId
        );
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
