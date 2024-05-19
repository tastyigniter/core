<?php

namespace Igniter\Admin\Classes;

use Igniter\Flame\Html\HtmlFacade as Html;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Support\HtmlString;

/**
 * Template Class
 */
class Template
{
    protected ?string $themeCode = null;

    protected ?string $pageTitle = null;

    protected ?string $pageHeading = null;

    protected array $pageButtons = [];

    public array $blocks = [];

    public array $renderHooks = [];

    /**
     * Returns the layout block contents but does not deletes the block from memory.
     *
     * @param string $name Specifies the block name.
     * @param ?string $default Specifies a default block value to use if the block requested is not exists.
     */
    public function getBlock(string $name, ?string $default = null): HtmlString
    {
        return new HtmlString($this->blocks[$name] ?? $default);
    }

    /**
     * Appends a content of the layout block.
     *
     * @param string $name Specifies the block name.
     * @param string $contents Specifies the block content.
     */
    public function appendBlock(string $name, string $contents)
    {
        if (!isset($this->blocks[$name])) {
            $this->blocks[$name] = null;
        }

        $this->blocks[$name] .= $contents;
    }

    /**
     * Sets a content of the layout block.
     *
     * @param string $name Specifies the block name.
     * @param string $contents Specifies the block content.
     */
    public function setBlock(string $name, string $contents)
    {
        $this->blocks[$name] = $contents;
    }

    public function getTitle(): ?string
    {
        return $this->pageTitle;
    }

    public function getHeading(): ?string
    {
        return $this->pageHeading;
    }

    public function getButtonList(): string
    {
        return implode(PHP_EOL, $this->pageButtons);
    }

    public function setTitle(string $title)
    {
        $this->pageTitle = $title;
    }

    public function setHeading(string $heading)
    {
        if (strpos($heading, ':')) {
            [$normal, $small] = explode(':', $heading);
            $heading = $normal.'&nbsp;<small>'.$small.'</small>';
        }

        $this->pageHeading = $heading;
    }

    public function setButton(string $name, array $attributes = [])
    {
        $this->pageButtons[] = '<a'.Html::attributes($attributes).'>'.$name.'</a>';
    }

    public function renderHook(string $name): HtmlString
    {
        $hooks = array_map(fn(callable $hook) => (string)app()->call($hook),
            $this->renderHooks[$name] ?? [],
        );

        return new HtmlString(implode('', $hooks));
    }

    public function registerHook(string $name, \Closure $callback)
    {
        $this->renderHooks[$name][] = $callback;
    }

    public function renderStaticCss(): string
    {
        $file = 'igniter::build/css/static.css';
        if (!File::exists($file)) {
            return '';
        }

        return File::get($file);
    }
}
