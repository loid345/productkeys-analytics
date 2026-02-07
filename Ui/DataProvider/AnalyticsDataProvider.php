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
    private int $currentPage = 1;
    private int $pageSize = 20;
    private array $orders = [];

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
                    'id' => 'MIN(pk.id)',
                    'sku' => 'pk.sku',
                    'created_at' => 'MIN(pk.created_at)',
                    'total_keys' => 'COUNT(*)',
                    'sold_keys' => 'SUM(CASE WHEN pk.status = 1 THEN 1 ELSE 0 END)',
                    'free_keys' => 'SUM(CASE WHEN pk.status = 0 THEN 1 ELSE 0 END)'
                ]
            )
            ->group(['pk.sku']);

        foreach ($this->filters as $filter) {
            $this->applyFilter($select, $filter);
        }

        foreach ($this->orders as $order) {
            $select->order($order);
        }

        if ($this->pageSize > 0) {
            $select->limitPage($this->currentPage, $this->pageSize);
        }

        $items = $connection->fetchAll($select);
        $totals = $this->fetchTotals($connection, $productkeysTable);
        $totalRecords = $this->fetchTotalRecords($connection, $productkeysTable);

        $this->loadedData = [
            'totalRecords' => $totalRecords,
            'items' => $items,
            'totals' => $totals
        ];

        return $this->loadedData;
    }

    public function setLimit($offset, $size): void
    {
        $this->currentPage = max(1, (int)$offset);
        $this->pageSize = max(0, (int)$size);
    }

    public function addOrder($field, $direction): void
    {
        $direction = strtoupper((string)$direction) === 'DESC' ? 'DESC' : 'ASC';
        $fieldMap = [
            'sku' => 'pk.sku',
            'total_keys' => 'total_keys',
            'sold_keys' => 'sold_keys',
            'free_keys' => 'free_keys'
        ];

        if (!isset($fieldMap[$field])) {
            return;
        }

        $this->orders[] = $fieldMap[$field] . ' ' . $direction;
    }

    private function applyFilter($select, Filter $filter): void
    {
        $field = $filter->getField();
        $conditionType = $filter->getConditionType() ?: 'eq';
        $value = $filter->getValue();

        if ($value === null || $value === '' || $value === []) {
            return;
        }

        $fieldMap = [
            'sku' => 'pk.sku',
            'created_at' => 'pk.created_at',
            'fulltext' => 'pk.sku'
        ];

        if (!isset($fieldMap[$field])) {
            return;
        }

        $column = $fieldMap[$field];

        if ($field === 'created_at') {
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
            if ($conditionType === 'gteq') {
                $select->where($column . ' >= ?', $value);
                return;
            }
            if ($conditionType === 'lteq') {
                $select->where($column . ' <= ?', $value);
                return;
            }
        }

        if ($field === 'fulltext' || $conditionType === 'fulltext' || $conditionType === 'like') {
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

    private function fetchTotals($connection, string $productkeysTable): array
    {
        $totalsSelect = $connection->select()
            ->from(
                ['pk' => $productkeysTable],
                [
                    'total_keys' => 'COUNT(*)',
                    'sold_keys' => 'SUM(CASE WHEN pk.status = 1 THEN 1 ELSE 0 END)',
                    'free_keys' => 'SUM(CASE WHEN pk.status = 0 THEN 1 ELSE 0 END)'
                ]
            );

        foreach ($this->filters as $filter) {
            $this->applyFilter($totalsSelect, $filter);
        }

        $totals = $connection->fetchRow($totalsSelect) ?: [];
        return [
            'sku' => (string)__('Total'),
            'total_keys' => (int)($totals['total_keys'] ?? 0),
            'sold_keys' => (int)($totals['sold_keys'] ?? 0),
            'free_keys' => (int)($totals['free_keys'] ?? 0)
        ];
    }

    private function fetchTotalRecords($connection, string $productkeysTable): int
    {
        $countSelect = $connection->select()
            ->from(['pk' => $productkeysTable], [])
            ->columns(['total' => 'COUNT(DISTINCT pk.sku)']);

        foreach ($this->filters as $filter) {
            $this->applyFilter($countSelect, $filter);
        }

        return (int)$connection->fetchOne($countSelect);
    }
}
