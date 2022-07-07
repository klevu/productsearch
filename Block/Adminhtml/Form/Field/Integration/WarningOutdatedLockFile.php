<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration;

use Klevu\Search\Block\Adminhtml\Form\Field\Integration\Warnings\OutdatedLockFile;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class WarningOutdatedLockFile extends Fieldset
{
    /**
     * @param AbstractElement $element
     *
     * @return string
     * @throws LocalizedException
     */
    public function render(AbstractElement $element)
    {
        $layout = $this->getLayout();
        $block = $layout->createBlock(
            OutdatedLockFile::class,
            'klevu_search_information_outdated_lock_file_warning'
        );
        $block->setTemplate(
            'Klevu_Search::klevu/search/form/field/integration/warnings/outdatedlockfile.phtml'
        );

        return $block->toHtml();
    }
}
