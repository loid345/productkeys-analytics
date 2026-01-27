<?php

declare(strict_types=1);

if (!function_exists('__')) {
    function __(string $text): string
    {
        return $text;
    }
}

namespace Magento\Framework\App {
    if (!class_exists(ResourceConnection::class)) {
        class ResourceConnection
        {
        }
    }
}

namespace Magento\Framework\Api {
    if (!class_exists(Filter::class)) {
        class Filter
        {
            private string $field;
            private ?string $conditionType;
            private $value;

            public function __construct(string $field = '', $value = null, ?string $conditionType = null)
            {
                $this->field = $field;
                $this->value = $value;
                $this->conditionType = $conditionType;
            }

            public function getField(): string
            {
                return $this->field;
            }

            public function getConditionType(): ?string
            {
                return $this->conditionType;
            }

            public function getValue()
            {
                return $this->value;
            }
        }
    }
}

namespace Magento\Ui\DataProvider {
    if (!class_exists(AbstractDataProvider::class)) {
        abstract class AbstractDataProvider
        {
            public function __construct(
                string $name,
                string $primaryFieldName,
                string $requestFieldName,
                array $meta = [],
                array $data = []
            ) {
            }
        }
    }
}
