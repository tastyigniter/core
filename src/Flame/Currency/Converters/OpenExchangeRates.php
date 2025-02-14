<?php

declare(strict_types=1);

namespace Igniter\Flame\Currency\Converters;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenExchangeRates extends AbstractConverter
{
    const API_URL = 'https://openexchangerates.org/api/latest.json?app_id=%s&base=%s&symbols=%s';

    protected $appId;

    public function __construct(array $config = [])
    {
        $this->appId = $config['apiKey'] ?? '';
    }

    public function converterDetails(): array
    {
        return [
            'name' => 'Open Exchange Rates',
            'description' => 'Conversion services provided by Open Exchange Rates.',
        ];
    }

    public function getExchangeRates(string $base, array $currencies): array
    {
        if (!strlen($this->appId)) {
            return [];
        }

        $response = $this->cacheCallback($this->getCacheKey(), function() use ($base, $currencies) {
            return Http::get(sprintf(self::API_URL, $this->appId, $base, implode(',', $currencies)));
        });

        if ($response->json('error')) {
            Log::info($response->json('description'));
        }

        return $response->json('rates', []);
    }
}
