<?php

namespace Klevu\Search\Model\Product\Sync;

use InvalidArgumentException;
use Klevu\Search\Api\Data\HistoryInterface;
use Klevu\Search\Model\Product\Sync\ResourceModel\History as ResourceModel;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Phrase;

class History extends AbstractModel implements HistoryInterface
{
    const FIELD_PRODUCT_ID = 'product_id';
    const FIELD_PARENT_ID = 'parent_id';
    const FIELD_STORE_ID = 'store_id';
    const FIELD_ACTION = 'action';
    const FIELD_SUCCESS = 'success';
    const FIELD_MESSAGE = 'message';
    const FIELD_SYNCED_AT = 'synced_at';

    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @return int|null
     */
    public function getProductId()
    {
        $productId = $this->getData(self::FIELD_PRODUCT_ID);

        return is_numeric($productId) ? (int)$productId : null;
    }

    /**
     * @param int $productId
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function setProductId($productId)
    {
        if (!is_numeric($productId)) {
            throw new InvalidArgumentException('Product ID must be numeric');
        }
        $this->setData(self::FIELD_PRODUCT_ID, (int)$productId);
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        $parentId = $this->getData(self::FIELD_PARENT_ID);

        return is_numeric($parentId) ? (int)$parentId : 0;
    }

    /**
     * @param int|null $parentId
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function setParentId($parentId)
    {
        if (null !== $parentId && !is_numeric($parentId)) {
            throw new InvalidArgumentException('Parent ID must be numeric or null');
        }
        $parentId = $parentId ? (int)$parentId : 0;

        $this->setData(self::FIELD_PARENT_ID, $parentId);
    }

    /**
     * @return int|null
     */
    public function getStoreId()
    {
        $storeId = $this->getData(self::FIELD_STORE_ID);

        return is_numeric($storeId) ? (int)$storeId : null;
    }

    /**
     * @param int $storeId
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function setStoreId($storeId)
    {
        if (!is_numeric($storeId)) {
            throw new InvalidArgumentException('Store ID must be numeric');
        }
        $this->setData(self::FIELD_STORE_ID, (int)$storeId);
    }

    /**
     * @return int|null
     */
    public function getAction()
    {
        $action = $this->getData(self::FIELD_ACTION);

        return is_numeric($action) ? (int)$action : null;
    }

    /**
     * @param int $action
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function setAction($action)
    {
        if (!is_numeric($action)) {
            throw new InvalidArgumentException('Action value must be numeric');
        }
        $this->setData(self::FIELD_ACTION, (int)$action);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        $message = $this->getData(self::FIELD_MESSAGE);

        return $message ? (string)$message : '';
    }

    /**
     * @param Phrase|string $message
     *
     * @return void
     */
    public function setMessage($message)
    {
        if ($message instanceof Phrase) {
            $message = $message->getText();
        }
        if (!is_scalar($message)) {
            throw new InvalidArgumentException('Message value must be instance of Phrase or a string');
        }
        $this->setData(self::FIELD_MESSAGE, (string)$message);
    }

    /**
     * @return bool
     */
    public function getSuccess()
    {
        return (bool)$this->getData(self::FIELD_SUCCESS);
    }

    /**
     * @param bool $success
     *
     * @return void
     */
    public function setSuccess($success)
    {
        if (!is_bool($success)) {
            throw new InvalidArgumentException('Success value must be boolean');
        }

        $this->setData(self::FIELD_SUCCESS, $success);
    }

    /**
     * @return string|null
     */
    public function getSyncedAt()
    {
        $syncedAt = $this->getData(self::FIELD_SYNCED_AT);

        return $syncedAt ? (string)$syncedAt : null;
    }
}
