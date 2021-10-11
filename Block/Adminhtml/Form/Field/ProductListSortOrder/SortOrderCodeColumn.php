<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\ProductListSortOrder;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * Renderer for sort order options within ProductListSortOrder frontend model for
 *  Stores > Configuration
 */
class SortOrderCodeColumn extends Select
{
    /**
     * @var OptionSourceInterface
     */
    private $productListSortOrdersSource;

    /**
     * SortOrderCodeColumn constructor.
     * @param Context $context
     * @param OptionSourceInterface $productListSortOrdersSource
     * @param array $data
     */
    public function __construct(
        Context $context,
        OptionSourceInterface $productListSortOrdersSource,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->productListSortOrdersSource = $productListSortOrdersSource;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        $this->setDataUsingMethod('name', $value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputId($value)
    {
        $this->setId($value);

        return $this;
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->productListSortOrdersSource->toOptionArray());
        }

        return parent::_toHtml();
    }
}
