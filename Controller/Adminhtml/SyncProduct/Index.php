<?php

namespace Klevu\Search\Controller\Adminhtml\SyncProduct;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Klevu_Search::sync_product_grid';
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return Page
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addBreadcrumb(__('Catalog'), __('Catalog'));
        $resultPage->addBreadcrumb(__('Klevu Sync Products'), __('Klevu Sync Products'));
        $resultPage->setActiveMenu('Klevu_Search::catalog_sync_product');

        $resultPageConfig = $resultPage->getConfig();
        $resultPageTitle = $resultPageConfig->getTitle();
        $resultPageTitle->prepend(__('Klevu Sync - Products'));

        return $resultPage;
    }
}
