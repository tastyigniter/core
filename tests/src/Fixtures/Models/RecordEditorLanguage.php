<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Models;

use Igniter\System\Models\Language;

class RecordEditorLanguage extends Language
{
    protected $table = 'languages';

    public function getRecordEditorOptions(): array
    {
        return [];
    }

    public function attachRecordTo($model) {}
}
