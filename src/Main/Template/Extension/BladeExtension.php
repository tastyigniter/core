<?php

namespace Igniter\Main\Template\Extension;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\ViewFinderInterface;

class BladeExtension
{
    public function register()
    {
        Blade::directive('themeStyles', [$this, 'compilesThemeStyles']);
        Blade::directive('themeScripts', [$this, 'compilesThemeScripts']);

        Blade::directive('themePage', [$this, 'compilesThemePage']);
        Blade::directive('themeContent', [$this, 'compilesThemeContent']);

        Blade::directive('themeComponent', [$this, 'compilesThemeComponent']);
        Blade::directive('themeComponentIf', [$this, 'compilesThemeComponentIf']);
        Blade::directive('themeComponentWhen', [$this, 'compilesThemeComponentWhen']);
        Blade::directive('themeComponentUnless', [$this, 'compilesThemeComponentUnless']);
        Blade::directive('themeComponentFirst', [$this, 'compilesThemeComponentFirst']);

        Blade::directive('themePartial', [$this, 'compilesThemePartial']);
        Blade::directive('themePartialIf', [$this, 'compilesThemePartialIf']);
        Blade::directive('themePartialWhen', [$this, 'compilesThemePartialWhen']);
        Blade::directive('themePartialUnless', [$this, 'compilesThemePartialUnless']);
        Blade::directive('themePartialFirst', [$this, 'compilesThemePartialFirst']);
    }

    //
    //
    //

    public function compilesThemeStyles(string $expression): string
    {
        return "<?php echo \Igniter\System\Facades\Assets::getCss(); ?>\n".
            "<?php echo \$__env->yieldPushContent('styles'); ?>";
    }

    public function compilesThemeScripts(string $expression): string
    {
        return "<?php echo \Igniter\System\Facades\Assets::getJs(); ?>\n".
            "<?php echo \$__env->yieldPushContent('scripts'); ?>";
    }

    //
    //
    //

    public function compilesThemePage(string $expression): string
    {
        return '<?php echo controller()->renderPage(); ?>';
    }

    public function compilesThemeContent(string $expression): string
    {
        return "<?php echo controller()->renderContent({$expression}); ?>";
    }

    public function compilesThemeComponent(string $expression): string
    {
        return "<?php echo controller()->renderComponent({$expression}); ?>";
    }

    public function compilesThemeComponentIf(string $expression): string
    {
        return "<?php if (controller()->hasComponent({$expression})) echo controller()->renderComponent({$expression}); ?>";
    }

    public function compilesThemeComponentWhen($condition, string $expression): string
    {
        return !$condition ? '' : "<?php echo controller()->renderComponent({$expression}); ?>";
    }

    public function compilesThemeComponentUnless($condition, string $expression): string
    {
        return $condition ? '' : "<?php echo controller()->renderComponent({$expression}); ?>";
    }

    public function compilesThemeComponentFirst($components, string $expression): string
    {
        $component = Arr::first($components, function($component) {
            return controller()->hasComponent($component);
        });

        return "<?php echo controller()->renderComponent($component); ?>";
    }

    public function compilesThemePartial(string $expression): string
    {
        return "<?php echo controller()->renderPartial({$expression}); ?>";
    }

    public function compilesThemePartialIf(string $expression): string
    {
        return "<?php if (controller()->hasPartial({$expression})) echo controller()->renderPartial({$expression}); ?>";
    }

    public function compilesThemePartialWhen($condition, string $expression): string
    {
        return !$condition ? '' : "<?php echo controller()->renderPartial({$expression}); ?>";
    }

    public function compilesThemePartialUnless($condition, string $expression): string
    {
        return $condition ? '' : "<?php echo controller()->renderPartial({$expression}); ?>";
    }

    public function compilesThemePartialFirst($partials, string $expression): string
    {
        $partial = Arr::first($partials, function($partial) {
            return controller()->hasPartial($partial);
        });

        return "<?php echo controller()->renderPartial($partial); ?>";
    }

    //
    //
    //

    public function stripQuotes(string $string): string
    {
        return preg_replace("/[\"\']/", '', $string);
    }

    public function stripParentheses(string $expression): string
    {
        if (Str::startsWith($expression, '(')) {
            $expression = substr($expression, 1, -1);
        }

        return $expression;
    }

    public function appendPartialPath(string $expression): string
    {
        [$condition, $partial, $data] = str_contains($expression, ',')
            ? array_map('trim', explode(',', trim($expression, '()'), 2)) + ['', '', '[]']
            : [trim($expression, '()'), '', '[]'];

        $partial = $this->stripQuotes($partial);

        $partial = $this->guessViewName($partial, '_partials.');

        return sprintf('%s, %s, %s', $condition, '"'.$partial.'"', $data);
    }

    public function guessViewName(string $name, string $prefix = 'components.'): string
    {
        if (!Str::endsWith($prefix, '.')) {
            $prefix .= '.';
        }

        $delimiter = ViewFinderInterface::HINT_PATH_DELIMITER;

        if (str_contains($name, $delimiter)) {
            return Str::replaceFirst($delimiter, $delimiter.$prefix, $name);
        }

        return $prefix.$name;
    }
}
