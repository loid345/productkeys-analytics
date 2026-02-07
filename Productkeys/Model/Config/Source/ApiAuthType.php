<?php

namespace Dart\Productkeys\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ApiAuthType implements ArrayInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'basic', 'label' => __('Basic Authentication')],
            ['value' => 'bearer', 'label' => __('Bearer Token')],
            ['value' => 'api_key', 'label' => __('API Key')],
        ];
    }
}