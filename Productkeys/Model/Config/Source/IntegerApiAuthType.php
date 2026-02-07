<?php
namespace Dart\Productkeys\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use \Magento\Framework\DB\Ddl\Table;

class IntegerApiAuthType extends AbstractSource
{
    public function getAllOptions()
    {
        $this->_options = [
                            ['label' => 'Basic Authentication', 'value' => 0],
                            ['label' => 'Bearer Token', 'value' => 1],
                            ['label' => 'API Key', 'value' => 2]
                        ];
        return $this->_options;
    }

    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }

    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        return [
                $attributeCode => [
                    'unsigned' => false,
                    'default' => null,
                    'extra' => null,
                    'type' => Table::TYPE_INTEGER,
                    'nullable' => true,
                    'comment' => 'Product Key Attribute Options' . $attributeCode . ' column'
                ],
            ];
    }
}
