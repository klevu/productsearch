<?php

namespace Klevu\Search\Controller\Adminhtml\Integration;

use Klevu\Search\Api\Service\Account\GetAccountDetailsInterface;
use Klevu\Search\Api\Service\Account\UpdateEndpointsInterface;
use Klevu\Search\Service\Account\KlevuApi\GetAccountDetails;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Endpoints extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Klevu_Search::integration';

    /**
     * @var ResultJsonFactory`
     */
    private $resultJsonFactory;
    /**
     * @var GetAccountDetailsInterface
     */
    private $getAccountDetails;
    /**
     * @var UpdateEndpointsInterface
     */
    private $updateEndpoints;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        ResultJsonFactory $resultJsonFactory,
        GetAccountDetailsInterface $getAccountDetails,
        UpdateEndpointsInterface $updateEndpoints,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->getAccountDetails = $getAccountDetails;
        $this->updateEndpoints = $updateEndpoints;
        $this->logger = $logger;
    }

    /**
     * @return JsonResult
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        try {
            $this->updateEndpoints();
            $code = 200;
            $message = __('Success: Klevu Endpoints Updated.');
            $success = true;
        } catch (NoSuchEntityException $exception) {
            $code = 404;
            $message = $exception->getMessage();
            $success = false;
        } catch (LocalizedException $exception) {
            $message = $exception->getMessage();
            $code = $exception->getCode();
            $success = false;
        } catch (\Exception $exception) {
            $message = (string)__('An internal error occurred. Please check logs for details');
            $code = $exception->getCode() ?: 500;
            $success = false;
            $this->logger->error($exception->getMessage(), [
                'method' => __METHOD__,
            ]);
        }
        $resultJson->setHttpResponseCode($code);
        $resultJson->setStatusHeader($code, null, $message);

        return $resultJson->setData([
            'success' => $success,
            'code' => $code,
            'message' => $message
        ]);
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     * @throws \Klevu\Search\Exception\InvalidApiResponseException
     * @throws \Zend_Validate_Exception
     */
    private function updateEndpoints()
    {
        $apiKeys = $this->getApiKeys();
        $storeId = $this->_request->getParam('store_id');
        $accountDetails = $this->getAccountDetails->execute($apiKeys, $storeId);
        $this->updateEndpoints->execute($accountDetails, $storeId);
    }

    /**
     * @return array
     */
    private function getApiKeys()
    {
        $params = $this->_request->getParams();

        return [
            GetAccountDetails::REQUEST_PARAM_JS_API_KEY => isset($params[GetAccountDetails::REQUEST_PARAM_JS_API_KEY]) ?
                $params[GetAccountDetails::REQUEST_PARAM_JS_API_KEY] :
                null,
            GetAccountDetails::REQUEST_PARAM_REST_API_KEY => isset($params[GetAccountDetails::REQUEST_PARAM_REST_API_KEY]) ?
                $params[GetAccountDetails::REQUEST_PARAM_REST_API_KEY] :
                null
        ];
    }
}
