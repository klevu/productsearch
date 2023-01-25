<?php

namespace Klevu\Search\Test\Integration\Traits\Klevu\Api\Mock;

use Klevu\Search\Model\Api\Action\Addrecords;
use Klevu\Search\Model\Api\Action\Deleterecords;
use Klevu\Search\Model\Api\Action\Updaterecords;
use Klevu\Search\Model\Api\Response as ApiResponse;
use Klevu\Search\Model\Context;
use Klevu\Search\Model\Product\KlevuProductActions;
use Klevu\Search\Model\Product\MagentoProductActions;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;

trait SyncApiMock
{
    use SessionSuccessMock;

    /**
     * @param bool $add
     * @param bool $delete
     * @param bool $update
     *
     * @return void
     */
    public function mockSyncApiCalls($add = true, $delete = true, $update = true)
    {
        $this->setObjectManager();

        $this->objectManager->addSharedInstance(
            $this->getKlevuProductActionsMock($add, $delete, $update),
            KlevuProductActions::class
        );
        $this->objectManager->addSharedInstance(
            $this->getApiAddRecordsMock($add),
            Addrecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getApiDeleteRecordsMock($delete),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getApiUpdateRecordsMock($update),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getKlevuContextPartialMock($add, $delete, $update),
            Context::class
        );
        $this->objectManager->addSharedInstance(
            $this->getMagentoProductActionsPartialMock($add, $delete, $update),
            MagentoProductActions::class
        );
    }

    /**
     * @param bool $add
     * @param bool $delete
     * @param bool $update
     *
     * @return KlevuProductActions|MockObject
     */
    private function getKlevuProductActionsMock($add, $delete, $update)
    {
        // from trait SessionSuccessMock
        $mockKlevuProductActions = $this->getKlevuProductActionsMockWithSessionSuccess();

        $mockKlevuProductActions->method('executeAddProductsSuccess')
            ->willReturn($add);
        $mockKlevuProductActions->method('executeDeleteProductsSuccess')
            ->willReturn($delete);
        $mockKlevuProductActions->method('executeUpdateProductsSuccess')
            ->willReturn($update);

        return $mockKlevuProductActions;
    }

    /**
     * @param bool $returns
     *
     * @return Addrecords|MockObject
     */
    private function getApiAddRecordsMock($returns)
    {
        $response = $this->getMockBuilder(ApiResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response->method('isSuccess')
            ->willReturn($returns);

        $mockAddRecords = $this->getMockBuilder(Addrecords::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAddRecords->method('execute')
            ->willReturn($response);

        return $mockAddRecords;
    }

    /**
     * @param bool $returns
     *
     * @return Deleterecords|MockObject
     */
    private function getApiDeleteRecordsMock($returns)
    {
        $response = $this->getMockBuilder(ApiResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response->method('isSuccess')
            ->willReturn($returns);

        $mockDeleteRecords = $this->getMockBuilder(Deleterecords::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDeleteRecords->method('execute')
            ->willReturn($response);

        return $mockDeleteRecords;
    }

    /**
     * @param bool $returns
     *
     * @return Updaterecords|MockObject
     */
    private function getApiUpdateRecordsMock($returns)
    {
        $response = $this->getMockBuilder(ApiResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response->method('isSuccess')
            ->willReturn($returns);

        $mockUpdateRecords = $this->getMockBuilder(Updaterecords::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockUpdateRecords->method('execute')
            ->willReturn($response);

        return $mockUpdateRecords;
    }

    /**
     * @param bool $add
     * @param bool $delete
     * @param bool $update
     *
     * @return Context
     */
    private function getKlevuContextPartialMock($add, $delete, $update)
    {
        return $this->objectManager->create(Context::class, [
            'klevuProductAdd' => $this->getApiAddRecordsMock($add),
            'klevuProductDelete' => $this->getApiDeleteRecordsMock($delete),
            'klevuProductUpdate' => $this->getApiUpdateRecordsMock($update),
        ]);
    }

    /**
     * @param bool $add
     * @param bool $delete
     * @param bool $update
     *
     * @return MagentoProductActions
     */
    private function getMagentoProductActionsPartialMock($add, $delete, $update)
    {
        return $this->objectManager->create(MagentoProductActions::class, [
            'context' => $this->getKlevuContextPartialMock($add, $delete, $update),
        ]);
    }

    /**
     * @return void
     */
    private function setObjectManager()
    {
        if (null === $this->objectManager) {
            $this->objectManager = ObjectManager::getInstance();
        }
    }
}
