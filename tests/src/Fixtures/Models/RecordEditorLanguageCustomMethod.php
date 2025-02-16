<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class RecordEditorLanguageCustomMethod extends Model
{
    protected $table = 'languages';

    public function getTestFieldRecordEditorOptions()
    {
        return [];
    }

    public function attachRecordTo($model) {}
}
