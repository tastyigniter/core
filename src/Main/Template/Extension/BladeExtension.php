<?php

declare(strict_types=1);

namespace Igniter\Main\Template\Extension;

use Illuminate\Support\Facades\Blade;

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
        Blade::directive('themePartialEach', [$this, 'compilesThemePartialEach']);
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

    public function compilesThemeComponentWhen(string $expression): string
    {
        return "<?php echo controller()->renderComponentWhen($expression); ?>";
    }

    public function compilesThemeComponentUnless(string $expression): string
    {
        return "<?php echo controller()->renderComponentUnless($expression); ?>";
    }

    public function compilesThemeComponentFirst(string $expression): string
    {
        return "<?php echo controller()->renderComponentFirst($expression); ?>";
    }

    public function compilesThemePartial(string $expression): string
    {
        return "<?php echo controller()->renderPartial($expression); ?>";
    }

    public function compilesThemePartialIf(string $expression): string
    {
        return "<?php if (controller()->hasPartial($expression)) echo controller()->renderPartial($expression); ?>";
    }

    public function compilesThemePartialWhen(string $expression): string
    {
        return "<?php echo controller()->renderPartialWhen($expression); ?>";
    }

    public function compilesThemePartialUnless(string $expression): string
    {
        return "<?php echo controller()->renderPartialUnless($expression); ?>";
    }

    public function compilesThemePartialFirst(string $expression): string
    {
        return "<?php echo controller()->renderPartialFirst($expression); ?>";
    }

    public function compilesThemePartialEach(string $expression): string
    {
        return "<?php echo controller()->renderPartialEach($expression); ?>";
    }
}
