<?php

namespace Igniter\Tests\Flame\Database;

use Igniter\System\Models\Page;
use Illuminate\Database\Eloquent\SoftDeletes;

it('returns true when generateOnCreate is true and model does not exist', function() {
    $model = new class extends Page
    {
        protected $permalinkable = [
            'permalink_slug' => ['source' => 'title', 'generateOnCreate' => true],
        ];

        protected $attributes = ['permalink_slug' => 'Hello World'];
    };

    $model->title = 'Hello World';
    $model->permalink_slug = 'Hello World';
    $model->language_id = 1;
    $model->save();

    expect($model->permalink_slug)->toBe('hello-world');
});

it('converts model to string when slug source is null', function() {
    $model = new class extends Page
    {
        protected $permalinkable = [
            'permalink_slug' => ['source' => null],
        ];
    };

    $model->title = 'Hello World';
    $model->language_id = 1;
    $model->save();

    expect($model->permalink_slug)->toContain('titlehello-world');
});

it('converts model to string when slug source is callable', function() {
    $model = new class extends Page
    {
        public function permalinkable()
        {
            return ['permalink_slug' => ['source' => [$this, 'getSlug']]];
        }

        public function getSlug()
        {
            return 'Hello World';
        }
    };

    $model->language_id = 1;
    $model->save();

    expect($model->permalink_slug)->toContain('hello-world');
});

it('does not generate unique slug when generateUnique is false', function() {
    $model = new class extends Page
    {
        protected $permalinkable = [
            'permalink_slug' => ['source' => 'title', 'generateUnique' => false],
        ];
    };

    $model->title = 'Hello World';
    $model->language_id = 1;
    $model->save();

    expect($model->permalink_slug)->toBe('hello-world');
});

it('returns current slug if it matches the existing slug', function() {
    $model = Page::factory()->create(['permalink_slug' => 'hello-world']);
    $model->title = 'Hello World';
    $model->permalink_slug = null;
    $model->save();

    expect($model->permalink_slug)->toBe('hello-world');
});

it('returns suffixed slug when similar slugs exist', function() {
    Page::factory()->create(['permalink_slug' => 'hello-world']);
    $model = new class extends Page
    {
        use SoftDeletes;

        protected $permalinkable = [
            'permalink_slug' => [
                'source' => 'title',
                'includeTrashed' => true,
            ],
        ];

        public function scopeWithUniqueSlugConstraints($query)
        {
            return $query;
        }
    };
    $model->fill([
        'title' => 'Hello World',
        'permalink_slug' => null,
        'language_id' => 1,
    ])->save();

    expect($model->permalink_slug)->toBe('hello-world-1');
});

it('appends suffix when slug is in reserved list and uniqueSuffix is null', function() {
    $model = new class extends Page
    {
        protected $permalinkable = [
            'permalink_slug' => [
                'source' => 'title',
                'reserved' => ['hello-world'],
            ],
        ];
    };
    $model->fill([
        'title' => 'Hello World',
        'permalink_slug' => null,
        'language_id' => 1,
    ])->save();

    expect($model->permalink_slug)->toBe('hello-world-1');
});

it('appends suffix from uniqueSuffix method when slug is in reserved list', function() {
    $model = new class extends Page
    {
        public function permalinkable()
        {
            return [
                'permalink_slug' => [
                    'source' => 'title',
                    'reserved' => function($model) {
                        return ['hello-world'];
                    },
                    'uniqueSuffix' => function($slug, $separator, $list) {
                        return '100';
                    },
                ],
            ];
        }
    };
    $model->fill([
        'title' => 'Hello World',
        'permalink_slug' => null,
        'language_id' => 1,
    ])->save();

    expect($model->permalink_slug)->toBe('hello-world-100');
});

it('throws exception when uniqueSuffix method is not null or closure', function() {
    $model = new class extends Page
    {
        protected $permalinkable = [
            'permalink_slug' => [
                'source' => 'title',
                'reserved' => ['hello-world'],
                'uniqueSuffix' => 'invalid',
            ],
        ];
    };
    $model->fill([
        'title' => 'Hello World',
        'permalink_slug' => null,
        'language_id' => 1,
    ]);

    expect(fn() => $model->save())
        ->toThrow('Sluggable "uniqueSuffix" for '.$model::class.':permalink_slug is not null, or a closure.');
});

it('throws exception when reserved is not array or closure', function() {
    $model = new class extends Page
    {
        protected $permalinkable = [
            'permalink_slug' => [
                'source' => 'title',
                'reserved' => 'invalid',
            ],
        ];
    };
    $model->fill([
        'title' => 'Hello World',
        'permalink_slug' => null,
        'language_id' => 1,
    ]);

    expect(fn() => $model->save())
        ->toThrow('Sluggable "reserved" for '.$model::class.':permalink_slug is not null, an array, or a closure that returns null/array.');
});

it('returns last element of suffix array', function() {
    $model = new class extends Page
    {
        public function permalinkable()
        {
            return [
                'permalink_slug' => [
                    'source' => 'title',
                    'reserved' => [$this->getKey() => 'hello-world'],
                ],
            ];
        }
    };
    $model->fill([
        'title' => 'Hello World',
        'permalink_slug' => 'hello-world',
        'language_id' => 1,
    ])->save();
    $model->title = 'Hello World';
    $model->permalink_slug = null;
    $model->save();

    expect($model->permalink_slug)->toBe('hello-world-world');
});
