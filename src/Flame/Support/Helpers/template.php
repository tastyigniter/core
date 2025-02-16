<?php

declare(strict_types=1);

/**
 * Template helper functions
 */

use Igniter\Flame\Pagic\Environment;

if (!function_exists('pagic')) {
    function pagic(?string $name = null, array $vars = []): Environment|string
    {
        if (is_null($name)) {
            return resolve('pagic');
        }

        return resolve('pagic')->render($name, $vars);
    }
}

if (!function_exists('page')) {
    /**
     * Get the page content
     */
    function page(): string
    {
        return controller()->renderPage();
    }
}

if (!function_exists('content')) {
    /**
     * Load a content template file
     *
     * @param string $content
     */
    function content($content = '', array $data = []): string
    {
        return controller()->renderContent($content, $data);
    }
}

if (!function_exists('partial')) {
    /**
     * Load a partial template file
     *
     * @param string $partial
     *
     * @return string
     */
    function partial($partial = '', array $data = []): mixed
    {
        return controller()->renderPartial($partial, $data);
    }
}

if (!function_exists('has_component')) {
    /**
     * Check if a component is loaded
     *
     * @param string $component
     */
    function has_component($component = ''): bool
    {
        return controller()->hasComponent($component);
    }
}

if (!function_exists('component')) {
    /**
     * Check if Partial Area has rendered components
     *
     * @param string $component
     *
     * @return string
     */
    function component($component = '', array $params = []): string|false
    {
        return controller()->renderComponent($component, $params);
    }
}

if (!function_exists('get_title')) {
    /**
     * Get page title html tag
     * @return    string
     */
    function get_title()
    {
        return controller()->getPage()->title;
    }
}

if (!function_exists('html')) {
    function html(?string $html): \Illuminate\Support\HtmlString
    {
        return new \Illuminate\Support\HtmlString($html ?: '');
    }
}
