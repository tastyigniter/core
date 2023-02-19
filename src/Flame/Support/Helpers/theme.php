<?php

use Igniter\Main\Classes\ThemeManager;

/**
 * Theme helper functions
 */

// ------------------------------------------------------------------------

if (!function_exists('active_theme')) {
    /**
     * Get the active theme code of the specified domain
     *
     * @return null
     */
    function active_theme()
    {
        return resolve(ThemeManager::class)->getActiveThemeCode();
    }
}

// ------------------------------------------------------------------------

if (!function_exists('parent_theme')) {
    /**
     * Get the parent theme code of the specified domain
     *
     * @param string $theme
     *
     * @return null
     */
    function parent_theme($theme)
    {
        return resolve(ThemeManager::class)->findParentCode($theme);
    }
}

// ------------------------------------------------------------------------
