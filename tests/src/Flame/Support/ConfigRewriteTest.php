<?php

namespace Igniter\Tests\Flame\Support;

use Igniter\Flame\Support\ConfigRewrite;
use Igniter\Flame\Support\Facades\File;

it('writes new values to the config file', function() {
    $filePath = '/path/to/config.php';
    $newValues = ['key' => 'new_value'];
    $contents = "<?php return ['key' => 'old_value'];";

    File::shouldReceive('get')->with($filePath)->andReturn($contents);
    File::shouldReceive('put')->withArgs(function($filePath, $arg) {
        return str_contains($arg, "'key' => 'new_value'");
    });

    $configRewrite = new ConfigRewrite();
    expect($configRewrite->toFile($filePath, $newValues))->toContain("'key' => 'new_value'");
});

it('rewrites existing string value in config file', function() {
    $configRewrite = new ConfigRewrite();
    $contents = "<?php return ['array' => ['key' => 'old_value']];";
    $newValues = ['array.key' => 'new_value'];
    expect($configRewrite->toContent($contents, $newValues))->toContain("'key' => 'new_value'");

    $contents = '<?php return ["key" => "\'old_value\'"];';
    $newValues = ['key' => "'new_value'"];
    expect($configRewrite->toContent($contents, $newValues))->toContain('"key" => "\'new_value\'"');
});

it('rewrites existing integer value in config file', function() {
    $configRewrite = new ConfigRewrite();
    $contents = "<?php return ['key' => 123];";
    $newValues = ['key' => 456];
    $result = $configRewrite->toContent($contents, $newValues);
    expect($result)->toContain("'key' => 456");
});

it('rewrites existing boolean value in config file', function() {
    $configRewrite = new ConfigRewrite();
    $contents = "<?php return ['key' => true];";
    $newValues = ['key' => false];
    $result = $configRewrite->toContent($contents, $newValues);
    expect($result)->toContain("'key' => false");
});

it('rewrites existing null value in config file', function() {
    $configRewrite = new ConfigRewrite();
    $contents = "<?php return ['key' => 'not_null'];";
    $newValues = ['key' => null];
    $result = $configRewrite->toContent($contents, $newValues);
    expect($result)->toContain("'key' => null");
});

it('rewrites existing array value in config file', function() {
    $configRewrite = new ConfigRewrite();
    $contents = "<?php return ['key' => ['old_value']];";
    $newValues = ['key' => ['new_value']];
    $result = $configRewrite->toContent($contents, $newValues);
    expect($result)->toContain("'key' => ['new_value']");
});

it('throws exception when key does not exist in config file', function() {
    $configRewrite = new ConfigRewrite();
    $contents = "<?php return ['key' => 'value'];";
    $newValues = ['non_existent_key' => 'new_value'];
    expect(fn() => $configRewrite->toContent($contents, $newValues))
        ->toThrow('Unable to rewrite key "non_existent_key" in config, does it exist?');
});

it('validates rewritten values in config file', function() {
    $configRewrite = new ConfigRewrite();
    $contents = "<?php return ['key' => 'old_value'];";
    $newValues = ['key' => 'new_value'];
    $result = $configRewrite->toContent($contents, $newValues, true);
    expect($result)->toContain("'key' => 'new_value'");
});

it('does not validate rewritten values when validation is disabled', function() {
    $configRewrite = new ConfigRewrite();
    $contents = "<?php return ['key' => 'old_value'];";
    $newValues = ['key' => 'new_value'];
    $result = $configRewrite->toContent($contents, $newValues, false);
    expect($result)->toContain("'key' => 'new_value'");
});
