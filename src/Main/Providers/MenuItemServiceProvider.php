<?php

namespace Igniter\Main\Providers;

use Igniter\Main\Template\Page;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MenuItemServiceProvider extends ServiceProvider
{
    public function register() {}

    public function boot()
    {
        Event::listen('pages.menuitem.listTypes', function() {
            return [
                'theme-page' => 'igniter::main.pages.text_theme_page',
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function($type) {
            return Page::getMenuTypeInfo((string)$type);
        });

        Event::listen('pages.menuitem.resolveItem', function($item, $url, $theme) {
            if ($item->type == 'theme-page' && $theme) {
                return Page::resolveMenuItem($item, $url, $theme);
            }
        });
    }
}