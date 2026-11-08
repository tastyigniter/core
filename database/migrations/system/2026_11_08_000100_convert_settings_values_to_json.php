<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Settings values were stored either as PHP serialized arrays or as raw strings,
 * and read back with unserialize(). Convert every value to JSON so they are decoded
 * with json_decode() and can never instantiate PHP objects.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->orderBy('setting_id')->chunkById(500, function($settings): void {
            foreach ($settings as $setting) {
                DB::table('settings')
                    ->where('setting_id', $setting->setting_id)
                    ->update(['value' => $this->legacyToJson($setting->value)]);
            }
        }, 'setting_id');
    }

    protected function legacyToJson(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        // Only arrays were ever serialized. Anything else, including values that
        // contain objects, is kept as the literal string that was saved.
        $decoded = @unserialize($value, ['allowed_classes' => false]);
        if (!is_array($decoded) || $this->containsObject($decoded)) {
            $decoded = $value;
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
