<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Pagic\Parsers;

use Igniter\Flame\Pagic\Parsers\SectionParser;

it('parses content with data, code, and markup sections', function() {
    $content = <<<'EOF'
---
name: test

'[component1]':
    prop: value
---
<?php echo 'test'; ?>
---
<p>Test</p>
EOF;
    $result = SectionParser::parse($content);

    expect($result['settings'])->toHaveKey('name', 'test')
        ->and($result['settings'])->toHaveKey('components', ['component1' => ['prop' => 'value']])
        ->and($result['code'])->toBe("<?php echo 'test'; ?>")
        ->and($result['markup'])->toContain('<p>Test</p>');
});

it('parses content with data and markup sections', function() {
    $content = <<<'EOF'
---
name: test
---
<p>Test</p>
EOF;
    $result = SectionParser::parse($content);
    expect($result['settings'])->toBe(['name' => 'test'])
        ->and($result['code'])->toBeNull()
        ->and($result['markup'])->toContain('<p>Test</p>');
});

it('parses content with only markup section', function() {
    $content = <<<'EOF'
---
<p>Test</p>
EOF;

    $result = SectionParser::parse($content);
    expect($result['settings'])->toBeNull()
        ->and($result['code'])->toBe('')
        ->and($result['markup'])->toContain('<p>Test</p>');
});

it('parses content with only markup section no separator', function() {
    $content = <<<'EOF'
<p>Test</p>
EOF;

    $result = SectionParser::parse($content);
    expect($result['settings'])->toBeNull()
        ->and($result['code'])->toBeNull()
        ->and($result['markup'])->toContain('<p>Test</p>');
});

it('renders content with data, code, and markup sections', function() {
    $data = [
        'settings' => [
            'name' => 'test',
            'components' => ['component1' => ['prop' => 'value']],
        ],
        'code' => "<?php echo 'test'; ?>",
        'markup' => '<p>Test</p>',
    ];
    $expected = <<<'EOF'
---
name: test
'[component1]':
    prop: value
---
<?php
 echo 'test'; 
?>
---
<p>Test</p>
EOF;

    expect(SectionParser::render($data))->toBe($expected);
});

it('renders content with data and markup sections', function() {
    $data = [
        'settings' => ['name' => 'test'],
        'markup' => '<p>Test</p>',
    ];
    $result = SectionParser::render($data);
    expect($result)->toBe("---\nname: test\n---\n<p>Test</p>");
});

it('renders content with only markup section', function() {
    $data = [
        'markup' => '<p>Test</p>',
    ];
    $result = SectionParser::render($data);
    expect($result)->toBe('<p>Test</p>');
});
