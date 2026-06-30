<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Pagic;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Pagic\SandboxProfile;
use Igniter\Flame\Pagic\TemplateSandbox;

beforeEach(function(): void {
    $this->sandbox = resolve(TemplateSandbox::class);
});

it('rejects F-01 PoC payloads', function(string $payload): void {
    expect(fn() => $this->sandbox->assertSafe($payload, SandboxProfile::Mail))
        ->toThrow(SystemException::class);
})->with([
    '{{ \Igniter\Pages\Models\Page::find(1) }}',
    '{{ shell_exec("id") }}',
    '{{ resolve("db") }}',
    '{{ \Class::method() }}',
    '{{ $fn() }}',
    "{{ ('she'.'ll_exec')('id') }}",
    '@php echo "x"; @endphp',
    '{!! app("x") !!}',
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
    'order template' => [realpath(__DIR__.'/../../../../../ti-ext-cart/resources/views/mail/order.blade.php')],
    'default layout' => [realpath(__DIR__.'/../../../../resources/views/system/_mail/layouts/default.blade.php')],
    'button partial' => [realpath(__DIR__.'/../../../../resources/views/system/_mail/partials/button.blade.php')],
]);

it('accepts safe mail template expressions', function(): void {
    $this->sandbox->assertSafe('{{ $first_name }}', SandboxProfile::Mail);
    $this->sandbox->assertSafe('{!! $order_menu[\'menu_options\'] !!}', SandboxProfile::Mail);
    $this->sandbox->assertSafe('@if(!empty($order_menus))@foreach($order_menus as $order_menu){{ $order_menu[\'menu_name\'] }}@endforeach@endif', SandboxProfile::Mail);
    $this->sandbox->assertSafe("@lang('igniter.orange::default.button_back')", SandboxProfile::Mail);
    $this->sandbox->assertSafe('{{ lang(\'igniter.orange::default.button_back\') }}', SandboxProfile::Mail);
});

it('still rejects static calls outside string literals', function(): void {
    expect(fn() => $this->sandbox->assertSafe('{{ \\Class::method() }}', SandboxProfile::Mail))
        ->toThrow(SystemException::class);
});

it('preserves theme profile strip behaviour', function(): void {
    $input = 'Hello {!! $body !!} {{ shell_exec("id") }} @php echo 1; @endphp';

    expect($this->sandbox->sanitize($input, SandboxProfile::Theme))
        ->not->toContain('{!!')
        ->not->toContain('shell_exec')
        ->not->toContain('@php');
});

it('strips higher-order function bypass payloads from theme templates', function(string $payload, string $needle): void {
    expect($this->sandbox->sanitize($payload, SandboxProfile::Theme))
        ->not->toContain($needle);
})->with([
    'blade call_user_func' => ['{{ call_user_func("system", "id") }}', 'call_user_func'],
    'blade array_map' => ['{{ array_map("system", ["id"]) }}', 'array_map'],
    'blade getenv' => ['{{ getenv("DB_PASSWORD") }}', 'getenv'],
    'blade file read' => ['{{ fgets(fopen("/etc/passwd", "r")) }}', 'fopen'],
    'code call_user_func' => ['function onStart() { call_user_func("system", "id"); }', 'call_user_func'],
    'code array_map' => ['function onStart() { array_map("system", ["id"]); }', 'array_map'],
    'code getenv' => ['function onStart() { getenv("DB_PASSWORD"); }', 'getenv'],
    'code file read' => ['function onStart() { fgets(fopen("/etc/passwd", "r")); }', 'fopen'],
    'code preg_replace_callback' => ['function onStart() { preg_replace_callback("/.*/", "system", "id"); }', 'preg_replace_callback'],
    'code usort' => ['function onStart() { usort($a, "system"); }', 'usort'],
]);

it('allows safe unescaped output in mail profile', function(): void {
    $this->sandbox->assertSafe('{!! $body !!}', SandboxProfile::Mail);
});

it('neutralizes poisoned mail templates during sanitize', function(): void {
    $sanitized = $this->sandbox->sanitize('{{ shell_exec("id") }} safe {{ $name }}', SandboxProfile::Mail);

    expect($sanitized)->not->toContain('shell_exec')
        ->toContain('{{ $name }}');
});

function extractMailTemplateBladeSection(string $contents): string
{
    $sections = preg_split('/^==$/m', $contents);

    if (count($sections) >= 3) {
        return trim((string) end($sections));
    }

    return trim($contents);
}
