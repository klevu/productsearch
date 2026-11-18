<?php

namespace Klevu\Search\Test\Integration\Controller;

use Magento\Framework\Webapi\Rest\Response;
use Magento\TestFramework\TestCase\AbstractController;

class SwaggerTest extends AbstractController
{
    /**
     * @param string $path
     * @dataProvider schemaRequestProvider
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('schemaRequestProvider')]
    public function testSchemaRequest($path)
    {
        ob_start();
        try {
            $this->dispatch($path);
        } finally {
            ob_end_clean();
        }

        $this->assertMatchesRegularExpression('/200 Success/', $this->getResponse()->getBody());
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        if (!$this->_response) {
            $this->_response = $this->_objectManager->get(Response::class);
        }

        return $this->_response;
    }

    /**
     * @return array
     */
    public static function schemaRequestProvider()
    {
        return [
            ['rest/schema'],
            ['rest/schema?services=all'],
            ['rest/all/schema?services=all'],
            ['rest/default/schema?services=all'],
        ];
    }
}
