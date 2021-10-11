<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field;

use Klevu\Search\Block\Adminhtml\Form\Field\ProductListSortOrder\SortOrderCodeColumn;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Klevu\Search\Api\SerializerInterface;
use Magento\Framework\View\Element\BlockInterface;

/**
 * Frontend model to define sort order options in Stores > Configuration
 */
class ProductListSortOrder extends AbstractFieldArray
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SortOrderCodeColumn
     */
    private $sortOrderCodeRenderer;

    /**
     * ProductListSortOrder constructor.
     * @param Context $context
     * @param SerializerInterface $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        parent::_prepareToRender();

        $this->addColumn('value', [
            'label' => __('Value'),
            'class' => 'required-entry',
            'renderer' => $this->getSortOrderCodeRenderer(),
            'size' => '200',
        ]);
        $this->addColumn('label', [
            'label' => __('Label Override'),
            'size' => '100',
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Sort Order');
    }

    /**
     * {@inheritdoc}
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        parent::_prepareArrayRow($row);

        $options = [];
        $value = $row->getDataUsingMethod('value');
        if (null !== $value) {
            $sortOrderCodeRenderer = $this->getSortOrderCodeRenderer();
            $optionHash = $sortOrderCodeRenderer->calcOptionHash($value);

            $options['option_' . $optionHash] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return SortOrderCodeColumn|BlockInterface
     * @throws LocalizedException
     */
    private function getSortOrderCodeRenderer()
    {
        if (!$this->sortOrderCodeRenderer) {
            $layout = $this->getLayout();
            $this->sortOrderCodeRenderer = $layout->createBlock(SortOrderCodeColumn::class, '', [
                'data' => [
                    'is_render_to_js_template' => true,
                ]
            ]);
        }

        return $this->sortOrderCodeRenderer;
    }

    /**
     * Provides backward compatibility support for JSON serialized values which haven't been converted
     *  to array before the parent processes
     * This ensures that values read from config.xml are used when no override set
     * {@inheritdoc}
     * @return array|null
     */
    public function getArrayRows()
    {
        // Backward compatibility for < 2.3
        /** @var AbstractElement $element */
        $element = $this->getElement();
        if ($element && $element->getDataUsingMethod('value') && is_string($element->getDataUsingMethod('value'))) {
            try {
                $unserializedValue = $this->serializer->unserialize($element->getDataUsingMethod('value'));
                if (is_array($unserializedValue)) {
                    $element->setDataUsingMethod('value', $unserializedValue);
                }
            } catch (\Exception $e) {
                // Not a JSON serialized value, so no action to take here
            }
        }

        return parent::getArrayRows();
    }
}
