<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\Flame\Mail\MailParser;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\MailImporter;
use Igniter\System\Classes\MailManager;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use Override;

/**
 * Content imported from a registered mail view the builder could not turn into blocks.
 *
 * The block stores a reference to the view segment, never its content: the
 * content is read from the view file when compiling, so posted settings
 * cannot change it. When the file changes and the hash no longer matches, the
 * block compiles to nothing until it is replaced.
 */
final class Legacy extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'conditional' => false,
            'code' => 'legacy',
            'name' => 'igniter::system.mail_builder.block_legacy',
            'description' => 'igniter::system.mail_builder.block_legacy_description',
            'icon' => 'fa fa-file-code',
            'category' => 'hidden',
        ];
    }

    #[Override]
    public function defineFields(): array
    {
        return [
            'view' => ['type' => 'hidden'],
            'segment' => ['type' => 'hidden'],
            'hash' => ['type' => 'hidden'],
        ];
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return true;
    }

    #[Override]
    public function sanitize(array $settings): array
    {
        return [
            'view' => is_string($settings['view'] ?? null) ? $settings['view'] : '',
            'segment' => is_numeric($settings['segment'] ?? null) ? (int)$settings['segment'] : -1,
            'hash' => is_string($settings['hash'] ?? null) ? $settings['hash'] : '',
        ];
    }

    /**
     * Legacy content is compiled by MailBuilder::compileLegacy(), never through the writer.
     */
    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->none();
    }

    /**
     * The referenced view segment, or null when the view is not a registered
     * mail view of the mode or the segment changed.
     */
    public function resolveSegment(array $settings, CompileContext $context): ?string
    {
        $settings = $this->sanitize($settings);
        if (!$this->isRegisteredView($settings['view'], $context->mode)) {
            return null;
        }

        $html = $this->readViewSection($settings['view']);
        $segment = MailImporter::splitChunks($html, $context->isMode(CompileContext::MODE_TEMPLATE))[$settings['segment']] ?? null;

        if (is_null($segment) || !hash_equals(sha1($segment), $settings['hash'])) {
            return null;
        }

        return $segment;
    }

    #[Override]
    public function summary(array $settings): string
    {
        return lang('igniter::system.mail_builder.text_legacy_summary');
    }

    public function isRegisteredView(string $view, string $mode): bool
    {
        $manager = resolve(MailManager::class);

        return match ($mode) {
            CompileContext::MODE_TEMPLATE => array_key_exists($view, (array)$manager->listRegisteredTemplates()),
            CompileContext::MODE_LAYOUT => in_array($view, (array)$manager->listRegisteredLayouts(), true),
            CompileContext::MODE_PARTIAL => in_array($view, (array)$manager->listRegisteredPartials(), true),
            default => false,
        };
    }

    protected function readViewSection(string $view): string
    {
        return (string)rescue(function() use ($view) {
            /** @var View $viewObj */
            $viewObj = ViewFacade::make($view);

            return array_get(MailParser::parse(File::get($viewObj->getPath())), 'html', '');
        }, '', false);
    }
}
