<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Template\Extension;

use Illuminate\Support\Facades\Blade;

it('compiles theme styles directive', function() {
    expect(Blade::compileString('@themeStyles'))
        ->toBe("<?php echo \Igniter\System\Facades\Assets::getCss(); ?>\n<?php echo \$__env->yieldPushContent('styles'); ?>");
});

it('compiles theme scripts directive', function() {
    expect(Blade::compileString('@themeScripts'))
        ->toBe("<?php echo \Igniter\System\Facades\Assets::getJs(); ?>\n<?php echo \$__env->yieldPushContent('scripts'); ?>");
});

it('compiles theme page directive', function() {
    expect(Blade::compileString('@themePage'))
        ->toBe('<?php echo controller()->renderPage(); ?>');
});

it('compiles theme content directive', function() {
    expect(Blade::compileString("@themeContent('content')"))
        ->toBe("<?php echo controller()->renderContent('content'); ?>");
});

it('compiles theme component directive', function() {
    expect(Blade::compileString("@themeComponent('component')"))
        ->toBe("<?php echo controller()->renderComponent('component'); ?>");
});

it('compiles theme component if directive', function() {
    expect(Blade::compileString("@themeComponentIf('component')"))
        ->toBe("<?php if (controller()->hasComponent('component')) echo controller()->renderComponent('component'); ?>");
});

it('compiles theme component when directive', function() {
    expect(Blade::compileString("@themeComponentWhen(true, 'component')"))
        ->toBe("<?php echo controller()->renderComponentWhen(true, 'component'); ?>");
});

it('compiles theme component unless directive', function() {
    expect(Blade::compileString("@themeComponentUnless(false, 'component')"))
        ->toBe("<?php echo controller()->renderComponentUnless(false, 'component'); ?>");
});

it('compiles theme component first directive', function() {
    expect(Blade::compileString("@themeComponentFirst(['component1', 'component2'], ['data' => 'component1'])"))
        ->toBe("<?php echo controller()->renderComponentFirst(['component1', 'component2'], ['data' => 'component1']); ?>");
});

it('compiles theme partial directive', function() {
    expect(Blade::compileString("@themePartial('partial')"))
        ->toBe("<?php echo controller()->renderPartial('partial'); ?>");
});

it('compiles theme partial if directive', function() {
    expect(Blade::compileString("@themePartialIf('partial')"))
        ->toBe("<?php if (controller()->hasPartial('partial')) echo controller()->renderPartial('partial'); ?>");
});

it('compiles theme partial when directive', function() {
    expect(Blade::compileString("@themePartialWhen(true, 'partial')"))
        ->toBe("<?php echo controller()->renderPartialWhen(true, 'partial'); ?>");
});

it('compiles theme partial unless directive', function() {
    expect(Blade::compileString("@themePartialUnless(false, 'partial')"))
        ->toBe("<?php echo controller()->renderPartialUnless(false, 'partial'); ?>");
});

it('compiles theme partial first directive', function() {
    expect(Blade::compileString("@themePartialFirst(['partial1', 'partial2'], ['data' => 'partial1'])"))
        ->toBe("<?php echo controller()->renderPartialFirst(['partial1', 'partial2'], ['data' => 'partial1']); ?>");
});

it('compiles theme partial each directive', function() {
    expect(Blade::compileString("@themePartialEach('partial1', [['name' => 'John'], ['name' => 'Jane']])"))
        ->toBe("<?php echo controller()->renderPartialEach('partial1', [['name' => 'John'], ['name' => 'Jane']]); ?>");
});
