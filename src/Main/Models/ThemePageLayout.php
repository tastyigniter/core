<?php

declare(strict_types=1);

namespace Igniter\Main\Models;

use Igniter\Flame\Database\Model;
use Illuminate\Support\Carbon;

/**
 * Page builder sections saved for a theme page.
 *
 * @property int $id
 * @property string $theme_code
 * @property string $page_code
 * @property array<int, array<string, mixed>>|null $sections
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ThemePageLayout extends Model
{
    protected $table = 'theme_page_layouts';

    protected $fillable = ['theme_code', 'page_code', 'sections'];

    protected $casts = [
        'sections' => 'array',
    ];

    public $timestamps = true;

    public static function findFor(string $themeCode, string $pageCode): ?self
    {
        return self::query()
            ->where('theme_code', $themeCode)
            ->where('page_code', $pageCode)
            ->first();
    }
}
