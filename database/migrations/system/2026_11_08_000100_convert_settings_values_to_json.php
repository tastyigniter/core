<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Settings arrays were stored as PHP serialized strings and read back with unserialize().
 * Convert them to JSON so they are decoded with json_decode() and can never instantiate
 * PHP objects. Every other value is a plain string and is left as it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->convertValues(fn(?string $value): ?string => $this->serializedToJson($value));
    }

    protected function convertValues(callable $converter): void
    {
        DB::table('settings')->orderBy('setting_id')->chunkById(500, function($settings) use ($converter): void {
            foreach ($settings as $setting) {
                $value = $converter($setting->value);
                if ($value !== $setting->value) {
                    DB::table('settings')
                        ->where('setting_id', $setting->setting_id)
                        ->update(['value' => $value]);
                }
            }
        }, 'setting_id');
    }

    protected function serializedToJson(?string $value): ?string
    {
        if (is_null($value) || !str_starts_with($value, 'a:')) {
            return $value;
        }

        // Only arrays were ever serialized. Anything else, including values that
        // contain objects, is kept as the literal string that was saved.
        $decoded = @unserialize($value, ['allowed_classes' => false]);
        if (!is_array($decoded) || $this->containsObject($decoded)) {
            return $value;
        }

        return json_encode($decoded, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    protected function containsObject(array $value): bool
    {
        foreach ($value as $item) {
            if (is_object($item) || (is_array($item) && $this->containsObject($item))) {
                return true;
            }
        }

        return false;
    }
};
