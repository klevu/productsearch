<?php

namespace Klevu\Search\Block\Adminhtml\Wizard\Configure;

use Klevu\Search\Helper\Config as Klevu_Config;
use Klevu\Search\Model\Api\Action\Getplans as Klevu_Plans;
use Magento\Backend\Block\Template\Context as Template_Context;
use Magento\Framework\Exception\NoSuchEntityException;

class Userplan extends \Magento\Backend\Block\Template
{

    public function __construct(
        Template_Context $context,
        Klevu_Plans $klevuPlans,
        Klevu_Config $klevuConfig,
        array $data = [])
    {
        $this->_klevuPlans = $klevuPlans;
        $this->_klevuConfig = $klevuConfig;
        parent::__construct($context, $data);
    }


    /**
     * Return the submit URL for the user configuration form.
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('klevu_search/wizard/userplan_post');
    }

    /**
     * Return the base URL for the store.
     *
     * @return string
     */
    public function getStoreUrl()
    {
        return $this->getBaseUrl();
    }

    /**
     * Return plans from klevu server.
     *
     * @return array
     */
    public function getPlans()
    {
        $request = $this->getRequest();
        $storeId = $request->getParam('store_id');
        if ('' !== (string)$storeId) {
            try {
                $store = $this->_storeManager->getStore($storeId);
                $this->_klevuPlans->setDataUsingMethod('store', $store);
            } catch (NoSuchEntityException $e) {
                $this->_logger->error($e->getMessage());
            }
        }

        $extension_version = $this->_klevuConfig->getModuleInfo();
        $response = $this->_klevuPlans->execute([
            "store" => "magento",
            "extension_version" => (string)$extension_version,
        ]);

        if (!$response->isSuccess()) {
            $this->_logger->error('Error retrieving plans', [
                'message' => $response->getMessage(),
                'error' => $response->getDataUsingMethod('error'),
            ]);

            return [];
        }

        $plans = $response->getData();

        return isset($plans['plans']['plan'])
            ? $plans['plans']['plan']
            : [];
    }


}
