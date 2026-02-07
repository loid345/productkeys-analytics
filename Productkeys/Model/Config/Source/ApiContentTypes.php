<?php

namespace Dart\Productkeys\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ApiContentTypes implements ArrayInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'application/json', 'label' => __('JSON')],
            ['value' => 'application/xml', 'label' => __('XML')],
            ['value' => 'text/plain', 'label' => __('Plain Text')],
            // Add more content types as needed
        ];
    }
}