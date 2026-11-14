<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes\MailBuilder;

use Igniter\Flame\Exception\SystemException;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\MailBlockManager;
use Igniter\System\Classes\MailBuilder\MailBuilder;
use Igniter\System\MailBlocks\Heading;
use Igniter\Tests\System\Fixtures\TestMailBlock;
use stdClass;

it('lists the core blocks', function() {
    $blocks = resolve(MailBlockManager::class)->listBlocks();

    expect(array_keys($blocks))->toContain('heading', 'text', 'button', 'image', 'columns', 'divider', 'spacer', 'panel', 'promotion', 'subcopy', 'table',
        'preheader', 'header', 'content_slot', 'footer', 'slot', 'parameter', 'legacy');
});

it('registers blocks from extensions', function() {
    $extensionManager = mock(ExtensionManager::class)->makePartial();
    $extensionManager->shouldReceive('getRegistrationMethodValues')->with('registerMailBlocks')
        ->andReturn(['test.extension' => [TestMailBlock::class], 'other.extension' => []]);
    app()->instance(ExtensionManager::class, $extensionManager);

    $manager = new MailBlockManager;

    expect($manager->findBlock('test.extension::greeting'))->toBeInstanceOf(TestMailBlock::class);
});

it('registers, extends and removes blocks with callbacks', function() {
    $manager = resolve(MailBlockManager::class);

    $manager->registerCallback(function(MailBlockManager $manager) {
        $manager->registerBlock(new TestMailBlock);
        $manager->extendBlock('heading', fn(BaseMailBlock $block) => $block->addFields([
            'subtitle' => ['label' => 'Subtitle', 'type' => 'text'],
        ]));
        $manager->extendBlock('not_registered', fn() => null);
        $manager->removeBlock('spacer');
    });

    expect($manager->findBlock('test.extension::greeting'))->toBeInstanceOf(TestMailBlock::class)
        ->and($manager->findBlock('heading')->formFields())->toHaveKey('subtitle')
        ->and($manager->findBlock('spacer'))->toBeNull();
});

it('filters blocks by mode and context', function() {
    $manager = resolve(MailBlockManager::class);
    $manager->registerBlocks(fn() => [TestMailBlock::class]);
    $builder = resolve(MailBuilder::class);

    $template = $manager->listBlocks($builder->makeContext(CompileContext::MODE_TEMPLATE, 'included'));
    $excluded = $manager->listBlocks($builder->makeContext(CompileContext::MODE_TEMPLATE, 'excluded'));
    $layout = $manager->listBlocks($builder->makeContext(CompileContext::MODE_LAYOUT));

    expect($template)->toHaveKey('test.extension::greeting')
        ->and($template)->not->toHaveKey('content_slot')
        ->and($excluded)->not->toHaveKey('test.extension::greeting')
        ->and($layout)->toHaveKey('content_slot')
        ->and($layout)->not->toHaveKey('test.extension::greeting')
        ->and($manager->listAssets($builder->makeContext(CompileContext::MODE_TEMPLATE)))->toBe(['test-mail-block.js']);
});

it('throws when a block code is registered twice', function() {
    $manager = resolve(MailBlockManager::class);
    $manager->registerBlocks([Heading::class]);

    expect(fn() => $manager->listBlocks())->toThrow(SystemException::class, 'Mail block [heading] is already registered');
});

it('throws for invalid block definitions', function(mixed $definition) {
    $manager = new MailBlockManager;
    $manager->registerBlocks([$definition]);

    expect(fn() => $manager->listBlocks())->toThrow(SystemException::class);
})->with([
    fn() => new stdClass,
    fn() => new class extends BaseMailBlock
    {
        public function blockDetails(): array
        {
            return ['code' => 'invalid code'];
        }

        public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
        {
            return $writer->none();
        }
    },
]);

it('reloads blocks registered after the first load', function() {
    $manager = resolve(MailBlockManager::class);
    $manager->listBlocks();

    $manager->registerBlock(TestMailBlock::class);

    expect($manager->findBlock('test.extension::greeting'))->toBeInstanceOf(TestMailBlock::class);
});
