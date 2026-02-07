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
        $select = $connection->select()
            ->from(
                ['pk' => $productkeysTable],
                [
                    'sku' => 'pk.sku',
                    'total_keys' => 'COUNT(*)',
                    'sold_keys' => 'SUM(CASE WHEN pk.status = 1 THEN 1 ELSE 0 END)',
                    'free_keys' => 'SUM(CASE WHEN pk.status = 0 THEN 1 ELSE 0 END)'
                ]
            )
            ->group(['pk.sku']);

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
            'period' => 'pk.updated_at',
            'fulltext' => 'pk.sku'
        ];

        if (!isset($fieldMap[$field])) {
            return;
        }

        $column = $fieldMap[$field];

        if ($field === 'period') {
            if (is_array($value)) {
                if (!empty($value['from'])) {
                    $select->where($column . ' >= ?', $value['from']);
                }
                if (!empty($value['to'])) {
                    $select->where($column . ' <= ?', $value['to']);
                }
                return;
            }
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
            $select->where($column . ' LIKE ?', $this->ensureLikeWildcards($value));
            return;
        }

        $select->where($column . ' = ?', $value);
    }

    private function ensureLikeWildcards($value): string
    {
        $value = (string)$value;
        if (str_contains($value, '%')) {
            return $value;
        }

        return '%' . $value . '%';
    }

    private function calculateTotals(array $items): array
    {
        $totals = [
            'sku' => (string)__('Total'),
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
