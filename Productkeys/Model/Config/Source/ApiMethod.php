<?php
namespace Dart\Productkeys\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ApiMethod implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'POST', 'label' => __('POST')],
            ['value' => 'PUT', 'label' => __('PUT')],
            ['value' => 'GET', 'label' => __('GET')]
        ];
    }
}
