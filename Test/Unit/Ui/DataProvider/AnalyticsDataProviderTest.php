<?php

declare(strict_types=1);

namespace Dart\ProductkeysAnalytics\Test\Unit\Ui\DataProvider;

use Dart\ProductkeysAnalytics\Ui\DataProvider\AnalyticsDataProvider;
use Magento\Framework\App\ResourceConnection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AnalyticsDataProviderTest extends TestCase
{
    public function testCalculateTotalsAggregatesCounts(): void
    {
        $resourceConnection = $this->createMock(ResourceConnection::class);
        $provider = new AnalyticsDataProvider(
            'analytics_listing',
            'sku',
            'sku',
            $resourceConnection
        );

        $items = [
            [
                'total_keys' => '4',
                'sold_keys' => '1',
                'free_keys' => '3',
            ],
            [
                'total_keys' => 2,
                'sold_keys' => 2,
                'free_keys' => 0,
            ],
        ];

        $method = new ReflectionMethod($provider, 'calculateTotals');
        $method->setAccessible(true);
        $totals = $method->invoke($provider, $items);

        $this->assertSame('Total', $totals['sku']);
        $this->assertSame(6, $totals['total_keys']);
        $this->assertSame(3, $totals['sold_keys']);
        $this->assertSame(3, $totals['free_keys']);
    }
}
