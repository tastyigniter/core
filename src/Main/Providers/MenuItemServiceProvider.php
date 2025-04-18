<?php

declare(strict_types=1);

namespace Igniter\Main\Providers;

use Igniter\Main\Template\Page;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MenuItemServiceProvider extends ServiceProvider
{
    public function register() {}

    public function boot(): void
    {
        Event::listen('pages.menuitem.listTypes', fn(): array => [
            'theme-page' => 'igniter::main.pages.text_theme_page',
        ]);

        Event::listen('pages.menuitem.getTypeInfo', fn($type): ?array => Page::getMenuTypeInfo((string)$type));

        Event::listen('pages.menuitem.resolveItem', function($item, $url, $theme) {
            if ($item->type == 'theme-page' && $theme) {
                return Page::resolveMenuItem($item, $url, $theme);
            }
        });
    }
}
