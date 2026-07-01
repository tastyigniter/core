<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Support;

use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Support\MediaUploadValidator;

beforeEach(function() {
    $this->validator = resolve(MediaUploadValidator::class);
});

it('rejects dotfile uploads', function() {
    expect(fn() => $this->validator->validateFilename('.htaccess'))
        ->toThrow(ApplicationException::class, lang('igniter::main.media_manager.alert_dotfile_not_allowed'));
});

it('rejects denylisted filenames', function() {
    expect(fn() => $this->validator->validateFilename('web.config'))
        ->toThrow(ApplicationException::class, lang('igniter::main.media_manager.alert_unsafe_file_content'));
});

it('rejects htaccess content', function() {
    $payload = "SetHandler application/x-httpd-php\nAddType application/x-httpd-php .jpg";

    expect(fn() => $this->validator->validateAndSanitize('rules.txt', $payload))
        ->toThrow(ApplicationException::class, lang('igniter::main.media_manager.alert_unsafe_file_content'));
});

it('rejects polyglot jpeg with embedded php', function() {
    $payload = "\xFF\xD8\xFF".'<?php system($_GET["cmd"]); ?>';

    expect(fn() => $this->validator->validateAndSanitize('test.jpg', $payload))
        ->toThrow(ApplicationException::class, lang('igniter::main.media_manager.alert_unsafe_file_content'));
});

it('strips script tags from malicious svg uploads', function() {
    $payload = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="10" height="10"/></svg>';

    $sanitized = $this->validator->validateAndSanitize('logo.svg', $payload);

    expect($sanitized)->not->toContain('<script')
        ->and($sanitized)->toContain('<rect');
});

it('accepts legitimate jpeg uploads', function() {
    $payload = "\xFF\xD8\xFF\xD9";

    expect($this->validator->validateAndSanitize('photo.jpg', $payload))->toBe($payload);
});

it('accepts legitimate png uploads', function() {
    $payload = "\x89PNG\r\n\x1a\n".str_repeat("\0", 8);

    expect($this->validator->validateAndSanitize('photo.png', $payload))->toBe($payload);
});
