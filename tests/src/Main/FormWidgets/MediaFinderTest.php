<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Flame\Database\Attach\HasMedia;
use Igniter\Flame\Database\Attach\Media;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\FlashException;
use Igniter\Main\Classes\MediaItem;
use Igniter\Main\FormWidgets\MediaFinder;
use Igniter\System\Facades\Assets;
use Igniter\System\Models\Page;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\Tests\Flame\Database\Fixtures\TestModelForMedia;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('image', 'Image');
    $this->formField->displayAs('image');

    $this->formField->arrayName = 'theme';
    $this->mediaModel = new class extends Model
    {
        use HasMedia;

        public $mediable = ['thumb'];

        public function findMedia($mediaId): Media
        {
            return new Media(['id' => $mediaId, 'name' => 'filename.jpg']);
        }

        public function deleteMedia($mediaId): null
        {
            return null;
        }
    };
    $this->mediaFinderWidget = new MediaFinder($this->controller, $this->formField, [
        'model' => $this->mediaModel,
    ]);
});

it('initializes correctly', function() {
    expect($this->mediaFinderWidget->prompt)->toBe('lang:igniter::admin.text_empty')
        ->and($this->mediaFinderWidget->mode)->toBe('grid')
        ->and($this->mediaFinderWidget->isMulti)->toBeFalse()
        ->and($this->mediaFinderWidget->thumbOptions)->toBe([
            'fit' => 'contain',
            'width' => 122,
            'height' => 122,
        ])
        ->and($this->mediaFinderWidget->useAttachment)->toBeFalse();
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('formwidgets/repeater.js', 'repeater-js');
    Assets::shouldReceive('addJs')->once()->with('mediafinder.js', 'mediafinder-js');
    Assets::shouldReceive('addCss')->once()->with('mediafinder.css', 'mediafinder-css');

    $this->mediaFinderWidget->config['useAttachment'] = true;
    $this->mediaFinderWidget->assetPath = [];

    $this->mediaFinderWidget->loadAssets();
});

it('renders correctly', function() {
    expect($this->mediaFinderWidget->render())->toBeString();
});

it('returns media identifier when media is an instance of Media', function() {
    $media = new Media(['id' => 1]);

    expect($this->mediaFinderWidget->getMediaIdentifier($media))->toBe(1)
        ->and($this->mediaFinderWidget->getMediaIdentifier(null))->toBeNull()
        ->and($this->mediaFinderWidget->getMediaIdentifier('stringMedia'))->toBeNull();
});

it('returns media name when media is an instance of Media', function() {
    $media = new Media(['id' => 1, 'file_name' => 'filename.jpg']);
    expect($this->mediaFinderWidget->getMediaName($media))->toBe('filename.jpg')
        ->and($this->mediaFinderWidget->getMediaName('/path/to/media.jpg'))->toBe('path/to/media.jpg');
});

it('returns media path when media is an instance of Media', function() {
    $media = new Media(['id' => 1, 'name' => 'mediafilename.jpg']);
    expect($this->mediaFinderWidget->getMediaPath($media))->toBe('media/attachments/public/med/iaf/ile/mediafilename.jpg')
        ->and($this->mediaFinderWidget->getMediaPath('/path/to/media.jpg'))->toBe('path/to/media.jpg');
});

it('returns media thumb when media is an instance of Media', function() {
    $media = new Media(['id' => 1, 'name' => 'mediafilename.jpg']);
    expect($this->mediaFinderWidget->getMediaThumb($media))->toEndWith('media/attachments/public/med/iaf/ile/mediafilename.jpg')
        ->and($this->mediaFinderWidget->getMediaThumb('path/to/media.jpg'))->toEndWith('_122x122_contain.jpg')
        ->and($this->mediaFinderWidget->getMediaThumb('/'))->toBe('');
});

it('returns file type document when media has no extension', function() {
    $media = new Media(['id' => 1, 'file_name' => 'path/to/media']);
    expect($this->mediaFinderWidget->getMediaFileType($media))->toBe(MediaItem::FILE_TYPE_DOCUMENT);
});

it('returns file type image when media has image extension', function() {
    expect($this->mediaFinderWidget->getMediaFileType('path/to/media.jpg'))->toBe(MediaItem::FILE_TYPE_IMAGE);
});

it('returns file type audio when media has audio extension', function() {
    expect($this->mediaFinderWidget->getMediaFileType('path/to/media.mp3'))->toBe(MediaItem::FILE_TYPE_AUDIO);
});

it('returns file type video when media has video extension', function() {
    expect($this->mediaFinderWidget->getMediaFileType('path/to/media.mp4'))->toBe(MediaItem::FILE_TYPE_VIDEO);
});

it('returns file type document when media has unknown extension', function() {
    expect($this->mediaFinderWidget->getMediaFileType('path/to/media.txt'))->toBe(MediaItem::FILE_TYPE_DOCUMENT);
});

it('returns empty array when useAttachment is false on load attachment config', function() {
    $this->mediaFinderWidget->useAttachment = false;
    expect($this->mediaFinderWidget->onLoadAttachmentConfig())->toBeArray()->toBeEmpty();

    $this->mediaFinderWidget->useAttachment = true;
    expect($this->mediaFinderWidget->onLoadAttachmentConfig())->toBeArray()->toBeEmpty();
});

it('returns empty array when model does not use HasMedia trait on load attachment config', function() {
    $this->mediaFinderWidget->useAttachment = true;
    $this->mediaFinderWidget->model = new Page;
    request()->request->add(['media_id' => 1]);

    expect($this->mediaFinderWidget->onLoadAttachmentConfig())->toBeArray()->toBeEmpty();
});

it('loads attachment config form', function() {
    $this->mediaFinderWidget->useAttachment = true;
    request()->request->add(['media_id' => 123]);

    expect($this->mediaFinderWidget->onLoadAttachmentConfig())->toBeArray()->not()->toBeEmpty();
});

it('returns empty array when useAttachment is false on save attachment config', function() {
    $this->mediaFinderWidget->useAttachment = false;
    expect($this->mediaFinderWidget->onSaveAttachmentConfig())->toBeArray()->toBeEmpty();

    $this->mediaFinderWidget->useAttachment = true;
    expect($this->mediaFinderWidget->onSaveAttachmentConfig())->toBeArray()->toBeEmpty();
});

it('returns empty array when model does not use HasMedia trait on save attachment config', function() {
    $this->mediaFinderWidget->useAttachment = true;
    $this->mediaFinderWidget->model = new Page;
    request()->request->add(['media_id' => 1]);

    expect($this->mediaFinderWidget->onSaveAttachmentConfig())->toBeArray()->toBeEmpty();
});

it('saves attachment config form', function() {
    $this->mediaFinderWidget->useAttachment = true;
    request()->request->add(['media_id' => 123]);

    expect($this->mediaFinderWidget->onSaveAttachmentConfig())->toBeArray()->not()->toBeEmpty();
});

it('returns empty array when useAttachment is false on remove attachment', function() {
    $this->mediaFinderWidget->useAttachment = false;
    expect($this->mediaFinderWidget->onRemoveAttachment())->toBeNull();
});

it('returns empty array when model does not use HasMedia trait on remove attachment', function() {
    $this->mediaFinderWidget->useAttachment = true;
    $this->mediaFinderWidget->model = new Page;
    request()->request->add(['media_id' => 1]);

    expect($this->mediaFinderWidget->onRemoveAttachment())->toBeNull();
});

it('removes attachment correctly', function() {
    $this->mediaFinderWidget->useAttachment = true;
    request()->request->add(['media_id' => 123]);

    expect($this->mediaFinderWidget->onRemoveAttachment())->toBeNull();
});

it('returns empty array when useAttachment is false on add attachment', function() {
    $this->mediaFinderWidget->useAttachment = false;
    expect($this->mediaFinderWidget->onAddAttachment())->toBeArray()->toBeEmpty();
});

it('returns empty array when model does not use HasMedia trait on add attachment', function() {
    $this->mediaFinderWidget->useAttachment = true;
    $this->mediaFinderWidget->model = new Page;
    request()->request->add(['media_id' => 1]);

    expect($this->mediaFinderWidget->onAddAttachment())->toBeArray()->toBeEmpty();
});

it('throws exception when field is missing in mediable config', function() {
    $this->mediaFinderWidget->useAttachment = true;
    $this->mediaFinderWidget->model = new class extends Model
    {
        use HasMedia;

        public $mediable = [];
    };
    request()->request->add(['media_id' => 1]);

    expect(fn() => $this->mediaFinderWidget->onAddAttachment())
        ->toThrow(FlashException::class);
});

it('throws exception when adding attachment on a non existing model', function() {
    $this->mediaFinderWidget->useAttachment = true;
    $this->mediaFinderWidget->model = new class extends Model
    {
        use HasMedia;

        public $mediable = ['image'];
    };
    request()->merge([
        'media_id' => 1,
        'items' => [
            [
                'name' => 'media.jpg',
                'path' => 'path/to/media.jpg',
            ],
        ],
    ]);

    expect(fn() => $this->mediaFinderWidget->onAddAttachment())
        ->toThrow(FlashException::class);
});

it('adds attachment correctly', function() {
    $this->mediaFinderWidget->useAttachment = true;
    $this->mediaFinderWidget->model = new TestModelForMedia;
    $this->mediaFinderWidget->model->save();

    request()->merge([
        'media_id' => 1,
        'items' => [
            [
                'name' => 'media.jpg',
                'path' => 'path/to/media.jpg',
            ],
        ],
    ]);

    expect($this->mediaFinderWidget->onAddAttachment())->toBeArray()->not()->toBeEmpty();
});

it('returns array with null when getLoadValue is called with isMulti set to true', function() {
    $this->mediaFinderWidget->isMulti = true;
    $this->formField->value = [];
    expect($this->mediaFinderWidget->getLoadValue())->toBe([null]);
});

it('returns no save data constant when getSaveValue is called with formField hidden', function() {
    $this->mediaFinderWidget->useAttachment = true;
    $this->formField = new FormField('image', 'Image');
    $this->formField->hidden = true;
    expect($this->mediaFinderWidget->getSaveValue('anyValue'))->toBe(FormField::NO_SAVE_DATA);
});

it('returns value when getSaveValue is called with valid value', function() {
    $this->formField = new FormField('image', 'Image');
    $this->formField->disabled = false;
    $this->formField->hidden = false;
    expect($this->mediaFinderWidget->getSaveValue('validValue'))->toBe('validValue');
});
