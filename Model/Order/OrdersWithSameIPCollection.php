<?php

namespace Klevu\Search\Model\Order;

use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

/**
 * OrdersWithSameIPCollection collection model
 *
 * @SuppressWarnings(PHPMD)
 */
class OrdersWithSameIPCollection extends AbstractModel
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param ConfigHelper $configHelper
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     */
    public function __construct(
        ConfigHelper $configHelper,
        OrderCollectionFactory $orderCollectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        RequestInterface $request
    ) {
        $this->configHelper = $configHelper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->request = $request;
    }

    /**
     * Get store parameter from RequestInterface or fallback to getStore()
     *
     * @return int
     */
    public function getStoreId()
    {
        $storeParamId = $this->request->getParam('store');
        if (null !== $storeParamId) {
            return (int)$storeParamId;
        }

        try {
            $store = $this->storeManager->getStore();
            $storeId = (int)$store->getId();
        } catch (NoSuchEntityException $e) {
            $this->logger->warning($e->getMessage());
            $storeId = 0;
        }

        return $storeId;
    }

    /**
     * Checks with sales_order
     *
     * remote_ip, order_count
     * 127.0.0.1,    50
     * 172.16.0.1,   12
     * 1.1.1.1,      1
     * 1.2.3.4,      1
     *
     * =50/(50+12+1+1)
     *
     * @return bool
     */
    public function execute()
    {
        $storeIdToConsider = $this->getStoreId();

        // If not configured then no need to show
        $percentageOfOrders = (int)$this->configHelper->getPercentageOfOrders($storeIdToConsider);
        if (($percentageOfOrders <= 0 || $percentageOfOrders >= 100)
            || !$this->configHelper->isExtensionConfigured($storeIdToConsider)) {
            return false;
        }

        $daysToCalculate = (int)$this->configHelper->getDaysToCalculateOrders($storeIdToConsider);
        $lastDays = time() - ($daysToCalculate * 24 * 60 * 60);
        $orderFrom = date('Y-m-d', strtotime(
            date('Y-m-d', $lastDays)
        ));
        $orderTo = date('Y-m-d', strtotime(
            date('Y-m-d')
        ));

        $return = false;
        $rows = $this->getGroupedOrdersData($orderFrom, $orderTo, $storeIdToConsider);

        $percentageOfOrdersPct = $percentageOfOrders / 100;
        foreach ($rows as $row) {
            if ($percentageOfOrdersPct
                <
                $row['order_count'] / (array_sum(array_column($rows, 'order_count')))) {
                $return = true;
                break;
            }
        }

        return $return;
    }

    /**
     * @param string $orderFrom
     * @param string $orderTo
     * @param int $storeId
     *
     * @return array
     */
    private function getGroupedOrdersData(
        $orderFrom,
        $orderTo,
        $storeId
    ) {
        $return = [];
        try {
            $orders = $this->orderCollectionFactory->create();
            $select = $orders->getSelect();
            $select->reset(Select::COLUMNS);
            $select->columns(['remote_ip', 'order_count' => new \Zend_Db_Expr('COUNT(entity_id)')]);
            $select->where('created_at >= ?', $orderFrom);
            $select->where('created_at < ?', $orderTo);
            if ($storeId) {
                $select->where('store_id =?', $storeId);
            }
            $select->group('remote_ip');
            $select->order('order_count DESC');
            //Limit added intentionally
            $select->limit(1000);

            $return = $orders->getData();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), [
                'method' => __METHOD__,
                'originalException' => $e,
            ]);
        }

        return $return;
    }
}
