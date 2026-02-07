<?php

declare(strict_types=1);

namespace Dart\ProductkeysAnalytics\Test\Unit\Ui\DataProvider;

use Dart\ProductkeysAnalytics\Ui\DataProvider\AnalyticsDataProvider;
use Magento\Framework\App\ResourceConnection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AnalyticsDataProviderTest extends TestCase
{
    public function testEnsureLikeWildcardsAddsWildcardsWhenMissing(): void
    {
        $resourceConnection = $this->createMock(ResourceConnection::class);
        $provider = new AnalyticsDataProvider(
            'analytics_listing',
            'sku',
            'sku',
            $resourceConnection
        );

        $method = new ReflectionMethod($provider, 'ensureLikeWildcards');
        $method->setAccessible(true);
        $withWildcards = $method->invoke($provider, 'sku123');
        $keptWildcards = $method->invoke($provider, '%sku%');

        $this->assertSame('%sku123%', $withWildcards);
        $this->assertSame('%sku%', $keptWildcards);
    }
}
