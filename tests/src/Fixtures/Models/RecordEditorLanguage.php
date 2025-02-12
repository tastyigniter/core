<?php

namespace Igniter\Tests\Fixtures\Models;

use Igniter\System\Models\Language;

class RecordEditorLanguage extends Language
{
    protected $table = 'languages';

    public function getRecordEditorOptions()
    {
        return [];
    }

    public function attachRecordTo($model) {}
}
