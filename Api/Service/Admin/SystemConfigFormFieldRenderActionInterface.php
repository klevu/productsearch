<?php

namespace Klevu\Search\Api\Service\Admin;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

interface SystemConfigFormFieldRenderActionInterface
{
    /**
     * @param Field $field
     * @param AbstractElement $element
     * @return Field
     */
    public function applies(Field $field, AbstractElement $element);

    /**
     * @param Field $field
     * @param AbstractElement $element
     * @return AbstractElement[]
     */
    public function beforeRender(Field $field, AbstractElement $element);

    /**
     * @param Field $field
     * @param mixed $result
     * @param AbstractElement $element
     * @return mixed
     */
    public function afterRender(Field $field, $result, AbstractElement $element);
}
