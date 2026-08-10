<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Pagic;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Pagic\SandboxProfile;
use Igniter\Flame\Pagic\TemplateSandbox;
use Igniter\Pages\Models\Page;

beforeEach(function(): void {
    $this->sandbox = resolve(TemplateSandbox::class);
});

it('rejects F-01 PoC payloads', function(string $payload): void {
    expect(fn() => $this->sandbox->assertSafe($payload, SandboxProfile::Mail))
        ->toThrow(SystemException::class);
})->with([
    '{{ '.Page::class.'::find(1) }}',
    '{{ shell_exec("id") }}',
    '{{ \Class::method() }}',
    '{{ $fn() }}',
    "{{ ('she'.'ll_exec')('id') }}",
    '@php echo "x"; @endphp',
    '{{ call_user_func("system", "id") }}',
    '{{ array_map("system", ["id"]) }}',
    '{{ call_user_func_array("system", ["id"]) }}',
    '{{ getenv("DB_PASSWORD") }}',
    '{{ fgets(fopen("/etc/passwd", "r")) }}',
    '{{ preg_replace_callback("/.*/", "system", "id") }}',
    '{{ usort($items, "system") }}',
]);

it('accepts shipped mail template fixtures', function(string $fixturePath): void {
    $contents = file_get_contents($fixturePath);
    $blade = extractMailTemplateBladeSection($contents);

    $this->sandbox->assertSafe($blade, SandboxProfile::Mail);
})->with([
    'default layout' => [realpath(__DIR__.'/../../../../resources/views/system/_mail/layouts/default.blade.php')],
    'button partial' => [realpath(__DIR__.'/../../../../resources/views/system/_mail/partials/button.blade.php')],
]);

it('accepts safe mail template expressions', function(): void {
    $this->sandbox->assertSafe('{{ $first_name }}', SandboxProfile::Mail);
    $this->sandbox->assertSafe('{!! $order_menu[\'menu_options\'] !!}', SandboxProfile::Mail);
    $this->sandbox->assertSafe('@if(!empty($order_menus))@foreach($order_menus as $order_menu){{ $order_menu[\'menu_name\'] }}@endforeach@endif', SandboxProfile::Mail);
    $this->sandbox->assertSafe("@lang('igniter.orange::default.button_back')", SandboxProfile::Mail);
    $this->sandbox->assertSafe("{{ lang('igniter.orange::default.button_back') }}", SandboxProfile::Mail);
    $this->sandbox->assertSafe('{{ $location_logo->getThumb([\'height\' => 56]) }}', SandboxProfile::Mail);
    $this->sandbox->assertSafe('{{ media_thumb($location_logo, [\'height\' => 56]) }}', SandboxProfile::Mail);
    $this->sandbox->assertSafe('@forelse($order_menus as $order_menu){{ $order_menu[\'menu_name\'] }}@empty<p>No items</p>@endforelse', SandboxProfile::Mail);
    $this->sandbox->assertSafe('@switch($order_type)@case(\'delivery\')Delivery@break@default Collection@endswitch', SandboxProfile::Mail);
})->throwsNoExceptions();

it('allows extension registered mail template functions and methods', function(): void {
    $sandbox = new TemplateSandbox;
    $sandbox->registerAllowedFunctions(['currency_format']);
    $sandbox->registerAllowedMethods(['formatCurrency']);

    $sandbox->assertSafe('{{ currency_format($total) }}', SandboxProfile::Mail);
    $sandbox->assertSafe('{{ $order->formatCurrency() }}', SandboxProfile::Mail);
})->throwsNoExceptions();

it('still rejects static calls outside string literals', function(): void {
    expect(fn() => $this->sandbox->assertSafe('{{ \\Class::method() }}', SandboxProfile::Mail))
        ->toThrow(SystemException::class);
});

it('preserves theme profile strip behaviour', function(): void {
    $input = 'Hello {!! $body !!} {{ shell_exec("id") }} @php echo 1; @endphp';

    expect($this->sandbox->sanitize($input, SandboxProfile::Theme))
        ->not->toContain('shell_exec')
        ->not->toContain('@php');
});

it('keeps safe code when sanitizing a whole php code block', function(): void {
    $input = "<?php\nfunction onStart() {\n    \$this->page['title'] = 'Safe';\n}\n?>";

    expect($this->sandbox->sanitize($input, SandboxProfile::Theme))
        ->toContain('function onStart()')
        ->toContain("\$this->page['title'] = 'Safe'")
        ->not->toContain('<?php')
        ->not->toContain('?>');
});

it('treats a whole-string php block as a code section for assertSafe', function(): void {
    $safe = "<?php\nfunction onStart() {\n    \$this->page['title'] = 'Safe';\n}\n?>";
    $unsafe = "<?php\nfunction onStart() {\n    system('id');\n}\n?>";

    expect(fn() => $this->sandbox->assertSafe($safe, SandboxProfile::Theme))
        ->not->toThrow(SystemException::class)
        ->and(fn() => $this->sandbox->assertSafe($unsafe, SandboxProfile::Theme))
        ->toThrow(SystemException::class, 'Template contains unsafe content: Forbidden function: system')
        ->and(fn() => $this->sandbox->assertSafe('Hello <?php echo 1; ?>', SandboxProfile::Theme))
        ->toThrow(SystemException::class, 'Template contains unsafe content: PHP tags are not allowed');
});

it('strips higher-order function bypass payloads from theme templates', function(string $payload, string $needle): void {
    expect($this->sandbox->sanitize($payload, SandboxProfile::Theme))
        ->not->toContain($needle);
})->with([
    'blade call_user_func' => ['{{ call_user_func("system", "id") }}', 'call_user_func'],
    'blade array_map' => ['{{ array_map("system", ["id"]) }}', 'array_map'],
    'blade getenv' => ['{{ getenv("DB_PASSWORD") }}', 'getenv'],
    'blade file read' => ['{{ fgets(fopen("/etc/passwd", "r")) }}', 'fopen'],
    'php tag call_user_func' => ['<?php function onStart() { call_user_func("system", "id"); } ?>', 'call_user_func'],
    'php tag array_map' => ['<?php function onStart() { array_map("system", ["id"]); } ?>', 'array_map'],
    'php tag getenv' => ['<?php function onStart() { getenv("DB_PASSWORD"); } ?>', 'getenv'],
    'php tag file read' => ['<?php function onStart() { fgets(fopen("/etc/passwd", "r")); } ?>', 'fopen'],
    'php tag preg_replace_callback' => ['<?php function onStart() { preg_replace_callback("/.*/", "system", "id"); } ?>', 'preg_replace_callback'],
    'php tag usort' => ['<?php function onStart() { usort($a, "system"); } ?>', 'usort'],
    'blade php block usort' => ['@php usort($a, "system"); @endphp', 'usort'],
]);

it('does not treat dangerous function names in plain markup as code', function(): void {
    $markup = 'Call file() or system() from docs: count(items) and fopen are words only.';

    expect(fn() => $this->sandbox->assertSafe($markup, SandboxProfile::Mail))
        ->not->toThrow(SystemException::class)
        ->and($this->sandbox->sanitize($markup, SandboxProfile::Theme))->toBe($markup);
});

it('allows safe unescaped output in mail profile', function(): void {
    $this->sandbox->assertSafe('{!! $body !!}', SandboxProfile::Mail);
})->throwsNoExceptions();

it('neutralizes poisoned mail templates during sanitize', function(): void {
    $sanitized = $this->sandbox->sanitize('{{ shell_exec("id") }} safe {{ $name }}', SandboxProfile::Mail);

    expect($sanitized)->not->toContain('shell_exec')
        ->toContain('{{ $name }}');
});

function extractMailTemplateBladeSection(string $contents): string
{
    $sections = preg_split('/^==$/m', $contents);

    if (count($sections) >= 3) {
        return trim(end($sections));
    }

    return trim($contents);
}
