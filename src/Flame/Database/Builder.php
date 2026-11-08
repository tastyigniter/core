<?php

declare(strict_types=1);

namespace Igniter\Flame\Database;

use Igniter\Flame\Database\Concerns\ExtendsEloquentBuilder;
use Illuminate\Database\Eloquent\Builder as BuilderBase;

/**
 * TastyIgniter Database Manager Class
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends BuilderBase<TModel>
 */
class Builder extends BuilderBase
{
    use ExtendsEloquentBuilder;
}
