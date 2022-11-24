<?php

namespace Klevu\Search\Plugin\Admin\System\Config\Form\Field;

use Klevu\Search\Api\Service\Admin\SystemConfigFormFieldRenderActionInterface;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class RenderPlugin
{
    /**
     * @var SystemConfigFormFieldRenderActionInterface[]
     */
    private $renderActions = [];

    /**
     * @param SystemConfigFormFieldRenderActionInterface[] $renderActions
     */
    public function __construct(
        array $renderActions = []
    ) {
        ksort($renderActions);
        array_walk($renderActions, [$this, 'addRenderAction']);
    }

    /**
     * @param SystemConfigFormFieldRenderActionInterface $renderAction
     * @param string $key
     * @return void
     * @throws \InvalidArgumentException
     */
    private function addRenderAction($renderAction, $key)
    {
        if (null === $renderAction) {
            if (isset($this->renderActions[$key])) {
                unset($this->renderActions[$key]);
            }

            return;
        }

        if (!($renderAction instanceof SystemConfigFormFieldRenderActionInterface)) {
            throw new \InvalidArgumentException(sprintf(
                'Render Action (%s) must be instance of %s; %s provided',
                $key,
                SystemConfigFormFieldRenderActionInterface::class,
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                is_object($renderAction) ? get_class($renderAction) : gettype($renderAction)
            ));
        }

        $this->renderActions[$key] = $renderAction;
    }

    /**
     * @param Field $subject
     * @param AbstractElement $element
     * @return AbstractElement[]
     */
    public function beforeRender(Field $subject, AbstractElement $element)
    {
        foreach ($this->renderActions as $renderAction) {
            if (!$renderAction->applies($subject, $element)) {
                continue;
            }

            $renderAction->beforeRender($subject, $element);
        }

        return [$element];
    }

    /**
     * @param Field $subject
     * @param mixed $result
     * @param AbstractElement|null $element
     * @return mixed
     */
    public function afterRender(Field $subject, $result, $element = null)
    {
        if (!($element instanceof AbstractElement)) {
            return $result;
        }

        foreach ($this->renderActions as $renderAction) {
            if (!$renderAction->applies($subject, $element)) {
                continue;
            }

            $result = $renderAction->afterRender($subject, $result, $element);
        }

        return $result;
    }
}
