<?php

namespace Igniter\Flame\Currency\Converters;

use Exception;
use Illuminate\Support\Facades\Log;

class FixerIO extends AbstractConverter
{
    const API_URL = 'http://data.fixer.io/api/latest?access_key=%s&base=%s&symbols=%s';

    protected $accessKey;

    public function __construct(array $config = [])
    {
        $this->accessKey = $config['apiKey'];
    }

    public function converterDetails(): array
    {
        return [
            'name' => 'Fixer.io',
            'description' => 'Conversion services by Fixer.io',
        ];
    }

    public function getExchangeRates($base, array $currencies): array
    {
        $result = [];

        if (!strlen($this->accessKey)) {
            return $result;
        }

        try {
            $response = $this->getHttpClient()->get(
                sprintf(self::API_URL, $this->accessKey, $base, implode(',', $currencies))
            );

            $result = json_decode($response->getBody(), true);

            if (isset($result['success']) && !$result['success']) {
                throw new \RuntimeException('An error occurred when requesting currency exchange rates from fixer.io, check your api key.');
            }
        } catch (Exception $ex) {
            Log::info($ex->getMessage());
        }

        return $result['rates'] ?? [];
    }
}
