<?php
namespace Dart\Productkeys\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ApiToggle extends Field
{
    /**
     * Render field HTML
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '<td class="label"><label for="' . $element->getHtmlId() . '">' . $element->getLabel() . '</label></td>';
        $html .= '<td class="value">';
        $html .= $this->_getToggleHtml($element);
        $html .= '</td>';

        return $this->_decorateRowHtml($element, $html);
    }

    /**
     * Get toggle HTML
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getToggleHtml(AbstractElement $element)
    {
        $html = '<select id="' . $element->getHtmlId() . '" name="' . $element->getName() . '" ' . $element->serialize($element->getHtmlAttributes()) . '>';
        foreach ($element->getValues() as $option) {
            $html .= '<option value="' . $option['value'] . '">' . $option['label'] . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
}