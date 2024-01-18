<?php

namespace Igniter\Flame\Currency\Converters;

use Exception;
use Illuminate\Support\Facades\Log;

class OpenExchangeRates extends AbstractConverter
{
    const API_URL = 'https://openexchangerates.org/api/latest.json?app_id=%s&base=%s&symbols=%s';

    protected $appId;

    public function __construct(array $config = [])
    {
        $this->appId = $config['apiKey'];
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
        $result = [];

        try {
            $response = $this->getHttpClient()->get(
                sprintf(self::API_URL, $this->appId, $base, implode(',', $currencies))
            );

            $result = json_decode($response->getBody(), true);

            if (isset($result['error']) && $result['error']) {
                throw new \RuntimeException($result['description']);
            }

        } catch (Exception $ex) {
            Log::info($ex->getMessage());
        }

        return $result['rates'] ?? [];
    }
}
