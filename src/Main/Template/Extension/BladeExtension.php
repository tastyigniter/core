<?php

namespace Igniter\Main\Template\Extension;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\ViewFinderInterface;

class BladeExtension
{
    public function register()
    {
        Blade::directive('styles', [$this, 'compilesStyles']);
        Blade::directive('scripts', [$this, 'compilesScripts']);

        Blade::directive('partial', [$this, 'compilesPartial']);
        Blade::directive('partialIf', [$this, 'compilesPartialIf']);
        Blade::directive('partialWhen', [$this, 'compilesPartialWhen']);
        Blade::directive('partialUnless', [$this, 'compilesPartialUnless']);
        Blade::directive('partialFirst', [$this, 'compilesPartialFirst']);

        Blade::directive('componentPartial', [$this, 'compilesComponentPartial']);
        Blade::directive('componentPartialIf', [$this, 'compilesComponentPartialIf']);
        Blade::directive('themePage', [$this, 'compilesPage']);
        Blade::directive('themeContent', [$this, 'compilesThemeContent']);
        Blade::directive('themePartial', [$this, 'compilesThemePartial']);
        Blade::directive('themePartialIf', [$this, 'compilesThemePartialIf']);
    }

    //
    //
    //

    public function compilesStyles(string $expression): string
    {
        return "<?php echo \Igniter\System\Facades\Assets::getCss(); ?>\n".
            "<?php echo \$__env->yieldPushContent('styles'); ?>";
    }

    public function compilesScripts(string $expression): string
    {
        return "<?php echo \Igniter\System\Facades\Assets::getJs(); ?>\n".
            "<?php echo \$__env->yieldPushContent('scripts'); ?>";
    }

    public function compilesPartial(string $expression): string
    {
        $expression = $this->stripParentheses($expression);
        [$partial, $data] = str_contains($expression, ',')
            ? array_map('trim', explode(',', trim($expression, '()'), 2)) + ['', '[]']
            : [trim($expression, '()'), '[]'];

        $partial = $this->stripQuotes($partial);

        $partial = $this->guessViewName($partial, '_partials.');

        $expression = sprintf('%s, %s', '"'.$partial.'"', $data);

        return "<?php echo \$__env->make({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    public function compilesPartialIf(string $expression): string
    {
        $expression = $this->stripParentheses($expression);
        [$partial, $data] = str_contains($expression, ',')
            ? array_map('trim', explode(',', trim($expression, '()'), 2)) + ['', '[]']
            : [trim($expression, '()'), '[]'];

        $partial = $this->stripQuotes($partial);

        $partial = $this->guessViewName($partial, '_partials.');

        $expression = sprintf('%s, %s', '"'.$partial.'"', $data);

        return "<?php if (\$__env->exists({$expression})) echo \$__env->make({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    public function compilesPartialWhen(string $expression): string
    {
        $expression = $this->stripParentheses($expression);
        $expression = $this->appendPartialPath($expression);

        return "<?php echo \$__env->renderWhen($expression, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
    }

    public function compilesPartialUnless(string $expression): string
    {
        $expression = $this->stripParentheses($expression);
        $expression = $this->appendPartialPath($expression);

        return "<?php echo \$__env->renderWhen(! $expression, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
    }

    public function compilesPartialFirst(string $expression): string
    {
        $expression = $this->stripParentheses($expression);
        $expression = $this->appendPartialPath($expression);

        return "<?php echo \$__env->first({$expression}, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    //
    //
    //

    public function compilesThemeContent(string $expression): string
    {
        return "<?php echo controller()->renderContent({$expression}); ?>";
    }

    public function compilesComponentPartial(string $expression): string
    {
        return "<?php echo controller()->renderComponent({$expression}); ?>";
    }

    public function compilesComponentPartialIf(string $expression): string
    {
        return "<?php if (controller()->hasComponent({$expression})) echo controller()->renderComponent({$expression}); ?>";
    }

    public function compilesPage(string $expression): string
    {
        return '<?php echo controller()->renderPage(); ?>';
    }

    public function compilesThemePartial(string $expression): string
    {
        return "<?php echo controller()->renderPartial({$expression}); ?>";
    }

    public function compilesThemePartialIf(string $expression): string
    {
        return "<?php if (controller()->hasComponent({$expression})) echo controller()->renderPartial({$expression}); ?>";
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
