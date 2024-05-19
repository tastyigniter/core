<?php

namespace Igniter\Flame\Currency\Drivers;

use DateTime;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

class Database extends AbstractDriver
{
    protected DatabaseManager $database;

    /**
     * Create a new driver instance.
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->database = app('db')->connection($this->config('connection'));
    }

    public function create(array $params): bool
    {
        // Ensure the currency doesn't already exist
        if ($this->find($params['code'], 0) !== null) {
            return true;
        }

        // Created at stamp
        $created = new DateTime('now');

        $params = array_merge([
            'currency_name' => '',
            'currency_code' => '',
            'currency_symbol' => '',
            'format' => '',
            'currency_rate' => 1,
            'currency_status' => 0,
            // 'created_at' => $created,
            'updated_at' => $created,
        ], $params);

        return $this->database->table($this->config('table'))->insert($params);
    }

    public function all(): array
    {
        $collection = new Collection($this->database->table($this->config('table'))->get());

        return $collection->keyBy('currency_code')
            ->map(function($item) {
                $format = $item->thousand_sign.'0'.$item->decimal_sign.str_repeat('0', $item->decimal_position);

                return [
                    'currency_id' => $item->currency_id,
                    'currency_name' => $item->currency_name,
                    'currency_code' => strtoupper($item->currency_code),
                    'currency_symbol' => $item->currency_symbol,
                    'format' => $item->symbol_position
                        ? '1'.$format.$item->currency_symbol
                        : $item->currency_symbol.'1'.$format,
                    'currency_rate' => $item->currency_rate,
                    'currency_status' => $item->currency_status,
                    'updated_at' => $item->updated_at,
                    // 'updated_at' => $item->updated_at,
                ];
            })
            ->all();
    }

    public function find(string $code, ?int $active = 1): mixed
    {
        $query = $this->database->table($this->config('table'))
            ->where('currency_code', strtoupper($code));

        // Make active optional
        if (is_null($active) === false) {
            $query->where('currency_status', $active);
        }

        return $query->first();
    }

    public function update(string $code, array $attributes, ?DateTime $timestamp = null): int
    {
        $table = $this->config('table');

        // Create timestamp
        if (empty($attributes['updated_at']) === true) {
            $attributes['updated_at'] = new DateTime('now');
        }

        return $this->database->table($table)
            ->where('currency_code', strtoupper($code))
            ->update($attributes);
    }

    public function delete(string $code): int
    {
        $table = $this->config('table');

        return $this->database->table($table)
            ->where('currency_code', strtoupper($code))
            ->delete();
    }
}
