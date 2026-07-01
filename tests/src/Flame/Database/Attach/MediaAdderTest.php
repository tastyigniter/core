<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Attach;

use Igniter\Flame\Database\Attach\Media;
use Igniter\Flame\Database\Attach\MediaAdder;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Support\MediaUploadValidator;
use Igniter\Tests\Flame\Database\Fixtures\TestModelForMedia;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function() {
    Storage::fake('media');
    Relation::morphMap(['test_countries' => TestModelForMedia::class]);
    $this->model = new TestModelForMedia;
    $this->model->save();
});

it('rejects unsafe attachment uploads', function() {
    $file = UploadedFile::fake()->createWithContent('evil.jpg', "\xFF\xD8\xFF".'<?php phpinfo(); ?>');
    $media = $this->model->newMediaInstance();

    app()->instance(MediaUploadValidator::class, $validator = mock(MediaUploadValidator::class));
    $validator->shouldReceive('validateAndSanitize')
        ->once()
        ->andThrow(new ApplicationException(lang('igniter::main.media_manager.alert_unsafe_file_content')));

    expect(fn() => app(MediaAdder::class)
        ->on($media)
        ->useDisk('media')
        ->performedOn($this->model)
        ->fromFile($file))
        ->toThrow(ApplicationException::class);
});

it('stores sanitized attachment uploads', function() {
    $file = UploadedFile::fake()->image('photo.jpg');
    $media = $this->model->newMediaInstance();

    $media = app(MediaAdder::class)
        ->on($media)
        ->useDisk('media')
        ->performedOn($this->model)
        ->fromFile($file);

    expect($media)->toBeInstanceOf(Media::class)
        ->and(Storage::disk('media')->exists($media->getDiskPath()))->toBeTrue();
});
