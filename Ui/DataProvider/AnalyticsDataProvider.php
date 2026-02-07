<?php

declare(strict_types=1);

namespace Dart\ProductkeysAnalytics\Ui\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\App\ResourceConnection;
use Magento\Ui\DataProvider\AbstractDataProvider;

class AnalyticsDataProvider extends AbstractDataProvider
{
    private ResourceConnection $resourceConnection;
    private array $filters = [];
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ResourceConnection $resourceConnection,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->resourceConnection = $resourceConnection;
    }

    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function getData(): array
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        $connection = $this->resourceConnection->getConnection();
        $productkeysTable = $this->resourceConnection->getTableName('dart_productkeys');
        $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $eavAttributeTable = $this->resourceConnection->getTableName('eav_attribute');
        $productVarcharTable = $this->resourceConnection->getTableName('catalog_product_entity_varchar');
        $salesOrderTable = $this->resourceConnection->getTableName('sales_order');

        $select = $connection->select()
            ->from(
                ['pk' => $productkeysTable],
                [
                    'sku' => 'pk.sku',
                    'total_keys' => 'COUNT(*)',
                    'sold_keys' => 'SUM(CASE WHEN pk.status = 1 THEN 1 ELSE 0 END)',
                    'free_keys' => 'SUM(CASE WHEN pk.status = 1 THEN 0 ELSE 1 END)'
                ]
            )
            ->joinLeft(
                ['p' => $productEntityTable],
                'p.sku = pk.sku',
                []
            )
            ->joinLeft(
                ['ea' => $eavAttributeTable],
                "ea.attribute_code = 'name' AND ea.entity_type_id = p.entity_type_id",
                []
            )
            ->joinLeft(
                ['pv' => $productVarcharTable],
                'pv.attribute_id = ea.attribute_id AND pv.entity_id = p.entity_id AND pv.store_id = 0',
                ['product_name' => 'pv.value']
            )
            ->joinLeft(
                ['so' => $salesOrderTable],
                'so.increment_id = pk.orderinc_id',
                []
            )
            ->group(['pk.sku', 'pv.value']);

        foreach ($this->filters as $filter) {
            $this->applyFilter($select, $filter);
        }

        $items = $connection->fetchAll($select);
        $totals = $this->calculateTotals($items);

        $this->loadedData = [
            'totalRecords' => count($items),
            'items' => $items,
            'totals' => [$totals]
        ];

        return $this->loadedData;
    }

    private function applyFilter($select, Filter $filter): void
    {
        $field = $filter->getField();
        $conditionType = $filter->getConditionType() ?: 'eq';
        $value = $filter->getValue();

        $fieldMap = [
            'sku' => 'pk.sku',
            'product_name' => 'pv.value',
            'order_date' => 'so.created_at'
        ];

        if (!isset($fieldMap[$field])) {
            return;
        }

        $column = $fieldMap[$field];

        if ($field === 'order_date') {
            if ($conditionType === 'from') {
                $select->where($column . ' >= ?', $value);
                return;
            }
            if ($conditionType === 'to') {
                $select->where($column . ' <= ?', $value);
                return;
            }
        }

        if ($conditionType === 'like') {
            $select->where($column . ' LIKE ?', $value);
            return;
        }

        $select->where($column . ' = ?', $value);
    }

    private function calculateTotals(array $items): array
    {
        $totals = [
            'sku' => (string)__('Total'),
            'product_name' => '',
            'total_keys' => 0,
            'sold_keys' => 0,
            'free_keys' => 0
        ];

        foreach ($items as $item) {
            $totals['total_keys'] += (int)($item['total_keys'] ?? 0);
            $totals['sold_keys'] += (int)($item['sold_keys'] ?? 0);
            $totals['free_keys'] += (int)($item['free_keys'] ?? 0);
        }

        return $totals;
    }
}
