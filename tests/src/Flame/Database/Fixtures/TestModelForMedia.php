<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Fixtures;

use Igniter\Flame\Database\Attach\HasMedia;
use Igniter\Flame\Database\Model;
use Override;

class TestModelForMedia extends Model
{
    use HasMedia;

    public $table = 'countries';

    protected $primaryKey = 'country_id';

    public $timestamps = true;

    public $mediable = ['thumb', 'image', 'gallery' => ['multiple' => true]];

    #[Override]
    public function getMorphClass(): string
    {
        return 'test_countries';
    }
}
