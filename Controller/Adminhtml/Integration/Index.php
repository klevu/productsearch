<?php

namespace Klevu\Search\Controller\Adminhtml\Integration;

use Klevu\Search\Api\Service\Account\GetAccountDetailsInterface;
use Klevu\Search\Service\Account\KlevuApi\GetAccountDetails;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Klevu_Search::integration';

    /**
     * @var ResultJsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var GetAccountDetailsInterface
     */
    private $getAccountDetails;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        ResultJsonFactory $resultJsonFactory,
        GetAccountDetailsInterface $getAccountDetails,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->getAccountDetails = $getAccountDetails;
        $this->logger = $logger;
    }

    /**
     * @return JsonResult
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $company = '';
        $email = '';
        try {
            $apiKeys = $this->getApiKeys();
            $storeId = $this->_request->getParam('store_id');
            $accountDetails = $this->getAccountDetails->execute($apiKeys, $storeId);
            $code = 200;
            $message = __('API keys are valid');
            $success = true;
            $company = $accountDetails->getCompany();
            $email = $accountDetails->getEmail();
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
            'message' => $message,
            'company' => $success ? $company : null,
            'email' => $success ? $email : null
        ]);
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
