<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Currency\Converters;

use Igniter\Flame\Currency\Converters\AbstractConverter;

it('returns correct name from converter details', function() {
    $converter = new class extends AbstractConverter
    {
        public function converterDetails(): array
        {
            return ['name' => 'Open Exchange Rates'];
        }

        public function getExchangeRates(string $base, array $currencies): array
        {
            return [];
        }
    };

    expect($converter->getName())->toBe('Open Exchange Rates');
});

it('returns correct description from converter details', function() {
    $converter = new class extends AbstractConverter
    {
        public function converterDetails(): array
        {
            return ['description' => 'Conversion services provided by Open Exchange Rates.'];
        }

        public function getExchangeRates(string $base, array $currencies): array
        {
            return [];
        }
    };

    expect($converter->getDescription())->toBe('Conversion services provided by Open Exchange Rates.');
});

it('forgets cache when cache lifetime is set', function() {
    $converter = new class extends AbstractConverter
    {
        public function getExchangeRates(string $base, array $currencies): array
        {
            return [];
        }

        public function converterDetails(): array
        {
            return [];
        }

        public function testGetCacheDriver()
        {
            return $this->getCacheDriver();
        }
    };

    config(['currency.ratesCacheDuration' => 60]);
    $converter->forgetCache();

    expect($converter->forgetCache())->toBe($converter);
});
