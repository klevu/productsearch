<?php
namespace Klevu\Search\Block\Adminhtml\Form\Field\Store;

use Magento\Backend\Block\Template\Context as Template_Context;
use Klevu\Search\Model\Sync as Klevu_Sync;

class LockStatus extends \Magento\Config\Block\System\Config\Form\Field
{
    public function __construct(
        Template_Context $context,
        Klevu_Sync $klevuSync,
        array $data = []
    ) {
        $this->_klevuSync = $klevuSync;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $status = $this->_klevuSync->getKlevuLockStatus();
        if (!empty($status)) {
            $html = $status;
        } else {
            $html = __('No lock files detected.');
        }
        
        return $html;
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setData('scope', $element->getScope());

        // Remove the inheritance checkbox
        $element
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();

        return parent::render($element);
    }
}
