<?php

namespace Klevu\Search\Ui\Component\Control;

use Magento\Ui\Component\Control\Action;

class SyncAction extends Action
{
    /**
     * @return void
     */
    public function prepare()
    {
        $config = $this->getConfiguration();
        $context = $this->getContext();

        $store = $context->getRequestParam('store');
        if ($store && isset($config['url'])) {
            $config['url'] = rtrim($config['url'], '/') . '/store/' . $store;
        }
        $this->setData('config', (array)$config);
        parent::prepare();
    }
}
