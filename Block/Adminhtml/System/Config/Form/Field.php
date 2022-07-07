<?php

namespace Klevu\Search\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context as Template_Context;
use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;

/**
 * @deprecated
 * functionality moved to plugins in Klevu\Search\Plugin\Admin\System\Config\Form\Field
 */
class Field extends \Magento\Config\Block\System\Config\Form\Field
{
    public function __construct(
        Template_Context $context,
        Klevu_HelperManager $klevuHelperManager,
        array $data = []
    ) {
        $this->_klevuHelperManager = $klevuHelperManager;
        parent::__construct($context,$data);
        $this->_context = $context;
    }
}
