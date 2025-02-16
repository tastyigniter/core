<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Attach;

use Igniter\Flame\Database\Attach\HasMedia;
use Igniter\Flame\Database\Attach\Manipulator;
use Igniter\Flame\Database\Attach\Media;
use Igniter\System\Models\Country;

it('attaches media to model', function() {
    $model = new class extends Country
    {
        use HasMedia;

        public $mediable = ['thumb'];

        public function getMorphClass()
        {
            return 'countries';
        }
    };
    $model->save();
    $media = new Media([
        'name' => 'image.jpg',
        'disk' => 'image.jpg',
        'file_name' => 'image.jpg',
        'size' => 1000,
        'mime_type' => 'image/jpeg',
        'tag' => 'thumb',
    ]);
    $saved = $model->media()->save($media);

    expect($saved->name)->toBe('image.jpg')
        ->and($model->thumb->name)->toBe($saved->name)
        ->and($model->getDefaultTagName())->toBe('thumb')
        ->and($model->findMedia($saved->getKey()))->not()->toBeNull()
        ->and($model->hasMedia('thumb'))->toBeTrue()
        ->and($model->newQuery()->whereHasMedia('thumb')->exists())->toBeTrue()
        ->and($model->getThumb([], 'thumb'))->not()->toBeNull()
        ->and($media->outputThumb())->toBeNull()
        ->and($model->getThumbOrBlank([], 'non-existence-thumb'))->toBe(Manipulator::encodedBlankImageUrl());
});

it('attaches multiple media to model', function() {
    $model = new class extends Country
    {
        use HasMedia;

        public $mediable = ['gallery' => ['multiple' => true]];

        public function getMorphClass()
        {
            return 'countries';
        }
    };
    $model->save();
    $media1 = $model->newMediaInstance()->fill([
        'name' => 'image1.jpg',
        'disk' => 'image1.jpg',
        'file_name' => 'image1.jpg',
        'size' => 1000,
        'mime_type' => 'image/jpeg',
        'tag' => 'gallery',
    ]);
    $media2 = new Media([
        'name' => 'image2.jpg',
        'disk' => 'image2.jpg',
        'file_name' => 'image2.jpg',
        'size' => 1000,
        'mime_type' => 'image/jpeg',
        'tag' => 'gallery',
    ]);
    $model->media()->saveMany([$media1, $media2]);

    expect(count($model->gallery))->toBe(2);
});

it('throws exception when it can not find media', function() {
    $model = new class extends Country
    {
        use HasMedia;

        public $mediable = ['thumb'];

        public function getMorphClass()
        {
            return 'countries';
        }
    };

    expect(fn() => $model->findMedia(123))->toThrow(\RuntimeException::class);
});

it('filters media by custom properties when retrieving media', function() {
    $model = new class extends Country
    {
        use HasMedia;

        public $mediable = ['gallery' => ['multiple' => true]];

        public function getMorphClass()
        {
            return 'countries';
        }
    };
    $model->save();
    $media1 = new Media([
        'name' => 'image1.jpg',
        'disk' => 'image1.jpg',
        'file_name' => 'image1.jpg',
        'size' => 1000,
        'mime_type' => 'image/jpeg',
        'tag' => 'gallery',
        'custom_properties' => ['key' => 'value'],
    ]);
    $media2 = new Media([
        'name' => 'image2.jpg',
        'disk' => 'image2.jpg',
        'file_name' => 'image2.jpg',
        'size' => 1000,
        'mime_type' => 'image/jpeg',
        'tag' => 'gallery',
        'custom_properties' => ['key' => 'invalid-value'],
    ]);
    $media3 = new Media([
        'name' => 'image2.jpg',
        'disk' => 'image2.jpg',
        'file_name' => 'image2.jpg',
        'size' => 1000,
        'mime_type' => 'image/jpeg',
        'tag' => 'gallery',
    ]);
    $model->media()->saveMany([$media1, $media2, $media3]);

    expect($model->getMedia('gallery', [
        'key' => 'value',
    ]))->toHaveCount(1);
});

it('deletes media', function() {
    $model = new class extends Country
    {
        use HasMedia;

        public $mediable = ['gallery' => ['multiple' => true]];

        public function getMorphClass()
        {
            return 'countries';
        }
    };
    $model->save();

    $media1 = new Media(['name' => 'image1.jpg', 'tag' => 'gallery']);
    $media2 = new Media(['name' => 'image2.jpg', 'tag' => 'gallery']);
    $model->media()->saveMany([$media1, $media2]);

    $model->deleteMedia($media1);

    expect(count($model->fresh()->gallery))->toBe(1);
});

it('deletes media when parent is deleted', function() {
    $model = new class extends Country
    {
        use HasMedia;

        public $mediable = ['gallery' => ['multiple' => true]];

        public function getMorphClass()
        {
            return 'countries';
        }
    };
    $model->save();

    $media1 = new Media(['name' => 'image1.jpg', 'tag' => 'gallery']);
    $media2 = new Media(['name' => 'image2.jpg', 'tag' => 'gallery']);
    $model->media()->saveMany([$media1, $media2]);

    $model->delete();

    expect($model->media()->count())->toBe(0)
        ->and(Country::firstWhere('country_id', $model->getKey()))->toBeNull();
});

it('clears media tag', function() {
    $model = new class extends Country
    {
        use HasMedia;

        public $mediable = ['gallery' => ['multiple' => true]];

        public function getMorphClass()
        {
            return 'countries';
        }
    };
    $model->save();

    $media1 = new Media(['name' => 'image1.jpg', 'tag' => 'gallery']);
    $media2 = new Media(['name' => 'image2.jpg', 'tag' => 'gallery']);
    $model->media()->saveMany([$media1, $media2]);

    $model->clearMediaTag();

    expect(count($model->gallery))->toBe(0);
});
