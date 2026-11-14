<?php

declare(strict_types=1);

namespace Igniter\Main\Models;

use Igniter\Flame\Database\Model;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Layout;
use Illuminate\Support\Carbon;

/**
 * A theme page as edited in the page builder: its sections, and its settings when
 * the page file cannot be written (a locked theme, or a page without a file).
 *
 * @property int $id
 * @property string $theme_code
 * @property string $page_code
 * @property array<int, array<string, mixed>>|null $sections
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ThemePage extends Model
{
    protected $table = 'theme_pages';

    protected $fillable = ['theme_code', 'page_code', 'sections', 'settings'];

    protected $casts = [
        'sections' => 'array',
        'settings' => 'array',
    ];

    public $timestamps = true;

    /** @var array<string, array<string, array>> Saved page settings by theme code, read once per request. */
    protected static array $settingsCache = [];

    public static function findFor(string $themeCode, string $pageCode): ?self
    {
        return self::query()
            ->where('theme_code', $themeCode)
            ->where('page_code', $pageCode)
            ->first();
    }

    /**
     * Saves the settings of a page. Empty settings remove them, and the row too
     * when it holds no sections either.
     */
    public static function saveSettings(string $themeCode, string $pageCode, array $settings): void
    {
        $model = self::firstOrNew(['theme_code' => $themeCode, 'page_code' => $pageCode]);
        $model->settings = $settings ?: null;

        if ($model->settings === null && $model->sections === null) {
            $model->exists && $model->delete();
        } elseif ($model->exists || $model->settings !== null) {
            $model->save();
        }

        unset(self::$settingsCache[$themeCode]);
    }

    /**
     * Saved settings of a page by theme code, for pages as they load.
     */
    public static function settingsFor(string $themeCode, string $pageCode): array
    {
        if (!array_key_exists($themeCode, self::$settingsCache)) {
            self::$settingsCache[$themeCode] = (array)rescue(
                fn(): array => self::query()
                    ->where('theme_code', $themeCode)
                    ->whereNotNull('settings')
                    ->pluck('settings', 'page_code')
                    ->map(fn($settings): array => (array)$settings)
                    ->all(),
                [], false,
            );
        }

        return (array)(self::$settingsCache[$themeCode][$pageCode] ?? []);
    }

    /**
     * Saved settings of a page as it loads in a theme: a child theme also takes its parent's.
     */
    public static function resolveSettings(string $themeCode, string $pageCode): array
    {
        $parentCode = resolve(ThemeManager::class)->findTheme($themeCode)?->getParentName();

        return array_merge(
            $parentCode ? self::settingsFor($parentCode, $pageCode) : [],
            self::settingsFor($themeCode, $pageCode),
        );
    }

    /**
     * Layout options for the page settings form.
     */
    public function getLayoutOptions(): array
    {
        return Layout::getDropdownOptions($this->theme_code);
    }

    public static function clearSettingsCache(): void
    {
        self::$settingsCache = [];
    }
}
