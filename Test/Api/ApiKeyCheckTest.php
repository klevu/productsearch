<?php

namespace Klevu\Search\Test\Api;

use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\TestFramework\TestCase\WebapiAbstract;

class ApiKeyCheckTest extends WebapiAbstract
{
    const RESOURCE_PATH = '/V1/klevu/integration/getStoresUsingApiKeys';

    public function testReturnsEmptyArrayWhenApiNotIntegrated()
    {
        $this->markTestIncomplete();

        $restApiKey = 'klevu-rest-api-key';
        $jsApiKey = 'klevu-js-api-key';

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => RestRequest::HTTP_METHOD_POST,
            ]
        ];
        $requestData = ['rest_api_Key' => $restApiKey, 'js_api_key' => $jsApiKey];

        $response = $this->_webApiCall($serviceInfo, $requestData);

        $this->assertIsArray($response);
    }
}
