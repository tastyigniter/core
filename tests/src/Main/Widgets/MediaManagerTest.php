<?php

namespace Igniter\Tests\Main\Widgets;

use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Classes\MediaLibrary;
use Igniter\Main\Widgets\MediaManager;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\User\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->mediaManagerWidget = new MediaManager($this->controller);
});

it('initializes correctly', function() {
    expect($this->mediaManagerWidget->size)->toBe('large')
        ->and($this->mediaManagerWidget->rowSorting)->toBeFalse()
        ->and($this->mediaManagerWidget->chooseButton)->toBeFalse()
        ->and($this->mediaManagerWidget->chooseButtonText)->toBe('igniter::main.media_manager.text_choose')
        ->and($this->mediaManagerWidget->selectMode)->toBe('multi')
        ->and($this->mediaManagerWidget->selectItem)->toBeNull();
});

it('renders correctly', function() {
    expect($this->mediaManagerWidget->render())->toBeString()
        ->and($this->mediaManagerWidget->vars)->toHaveKeys([
            'currentFolder', 'isRootFolder', 'items', 'folderSize', 'totalItems',
            'folderList', 'folderTree', 'sortBy', 'filterBy', 'searchTerm',
            'isPopup', 'selectMode', 'selectItem', 'maxUploadSize', 'allowedExtensions',
            'chooseButton', 'chooseButtonText', 'breadcrumbs',
        ]);
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addCss')->once()->with('mediamanager.css', 'mediamanager-css');
    Assets::shouldReceive('addJs')->once()->with('mediamanager.js', 'mediamanager-js');
    Assets::shouldReceive('addJs')->once()->with('mediamanager.modal.js', 'mediamanager-modal-js');

    $this->mediaManagerWidget->assetPath = [];

    $this->mediaManagerWidget->loadAssets();
});

it('sets descending sorting correctly', function() {
    expect(fn() => $this->mediaManagerWidget->onSetSorting())->toThrow(ValidationException::class);

    request()->request->add(['sortBy' => 'name', 'direction' => 'ascending', 'path' => '/test-folder']);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    prepareMediaLibraryForRender($mediaLibrary);
    expect($this->mediaManagerWidget->onSetSorting())->toBeArray()
        ->and($this->mediaManagerWidget->getSession('media_sort_by'))->toBe(['name', 'descending'])
        ->and($this->mediaManagerWidget->getSession('media_folder'))->toBe('/test-folder');
});

it('sets ascending sorting correctly', function() {
    $this->mediaManagerWidget->putSession('media_sort_by', ['name', 'descending']);
    request()->request->add(['sortBy' => 'name', 'direction' => 'ascending', 'path' => '/test-folder']);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    prepareMediaLibraryForRender($mediaLibrary);
    expect($this->mediaManagerWidget->onSetSorting())->toBeArray()
        ->and($this->mediaManagerWidget->getSession('media_sort_by'))->toBe(['name', 'ascending'])
        ->and($this->mediaManagerWidget->getSession('media_folder'))->toBe('/test-folder');
});

it('fails validation when setting sorting', function() {
    request()->request->add(['sortBy' => 'name', 'direction' => 'invalid', 'path' => '/test-folder']);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnFalse();
    expect(fn() => $this->mediaManagerWidget->onSetSorting())->toThrow(ValidationException::class);
});

it('sets filter correctly', function() {
    expect(fn() => $this->mediaManagerWidget->onSetFilter())->toThrow(ValidationException::class);

    request()->request->add(['filterBy' => 'image']);
    expect($this->mediaManagerWidget->onSetFilter())->toBeArray()
        ->and($this->mediaManagerWidget->getSession('media_filter_by'))->toBe('image');
});

it('sets search term correctly', function() {
    expect(fn() => $this->mediaManagerWidget->onSearch())->toThrow(ValidationException::class);

    request()->request->add(['search' => 'filename']);
    expect($this->mediaManagerWidget->onSearch())->toBeArray()
        ->and($this->mediaManagerWidget->getSession('media_search'))->toBe('filename');
});

it('throws exception when validation fails in onGoToFolder', function() {
    expect(fn() => $this->mediaManagerWidget->onGoToFolder())->toThrow(ValidationException::class);
});

it('throws exception when folder does not exist in onGoToFolder', function() {
    request()->request->add(['path' => '/non-existence-folder']);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('exists')->with('/non-existence-folder')->andReturn(true, false);
    expect(fn() => $this->mediaManagerWidget->onGoToFolder())->toThrow(FlashException::class);
});

it('sets current folder correctly', function() {
    request()->request->add([
        'path' => '/test-folder',
        'resetCache' => true,
        'resetSearch' => true,
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    prepareMediaLibraryForRender($mediaLibrary);
    expect($this->mediaManagerWidget->onGoToFolder())->toBeArray()
        ->and($this->mediaManagerWidget->getSession('media_folder'))->toBe('/test-folder');
});

it('loads media manager in popup', function() {
    expect(fn() => $this->mediaManagerWidget->onLoadPopup())->toThrow(ValidationException::class);

    request()->request->add([
        'goToItem' => '/test-folder/item.png',
        'selectMode' => 'single',
        'chooseButton' => true,
        'chooseButtonText' => 'Choose',
    ]);

    expect($this->mediaManagerWidget->onLoadPopup())->toBeString()
        ->and($this->mediaManagerWidget->selectMode)->toBe('single')
        ->and($this->mediaManagerWidget->chooseButton)->toBeTrue()
        ->and($this->mediaManagerWidget->chooseButtonText)->toBe('Choose')
        ->and($this->mediaManagerWidget->selectItem)->toBe('item.png')
        ->and($this->mediaManagerWidget->getSession('media_folder'))->toBe('/test-folder');
});

it('throws exception when creating folder is disabled in onCreateFolder', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_new_folder', null)->andReturnFalse();
    expect(fn() => $this->mediaManagerWidget->onCreateFolder())->toThrow(FlashException::class);
});

it('throws exception when validation fails in onCreateFolder', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_new_folder', null)->andReturnTrue();
    expect(fn() => $this->mediaManagerWidget->onCreateFolder())->toThrow(ValidationException::class);
});

it('throws exception when folder already exists in onCreateFolder', function() {
    request()->request->add([
        'path' => '/test-folder',
        'name' => 'existing-folder',
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_new_folder', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder/existing-folder')->andReturnTrue();
    expect(fn() => $this->mediaManagerWidget->onCreateFolder())
        ->toThrow(FlashException::class, lang('igniter::main.media_manager.alert_file_exists'));
});

it('creates a new folder', function() {
    request()->request->add([
        'path' => '/test-folder',
        'name' => 'new-folder',
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_new_folder', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder/new-folder')->andReturnFalse();
    $mediaLibrary->shouldReceive('makeFolder')->with('/test-folder/new-folder')->once();
    $mediaLibrary->shouldReceive('resetCache')->once();
    prepareMediaLibraryForRender($mediaLibrary);
    expect($this->mediaManagerWidget->onCreateFolder())->toBeArray()
        ->and($this->mediaManagerWidget->getSession('media_folder'))->toBe('/test-folder/new-folder');
});

it('throws exception when renaming folder is disabled in onRenameFolder', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_rename', null)->andReturnFalse();
    expect(fn() => $this->mediaManagerWidget->onRenameFolder())->toThrow(FlashException::class);
});

it('throws exception when validation fails in onRenameFolder', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_rename', null)->andReturnTrue();
    expect(fn() => $this->mediaManagerWidget->onRenameFolder())->toThrow(ValidationException::class);
});

it('throws exception when folder already exist in onRenameFolder', function() {
    request()->request->add([
        'path' => '/test-folder',
        'name' => 'existing-folder',
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_rename', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/existing-folder')->andReturnTrue();
    expect(fn() => $this->mediaManagerWidget->onRenameFolder())
        ->toThrow(FlashException::class, lang('igniter::main.media_manager.alert_file_exists'));
});

it('renames a folder', function() {
    request()->request->add([
        'path' => '/test-folder',
        'name' => 'new-folder',
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_rename', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/new-folder')->andReturnFalse();
    $mediaLibrary->shouldReceive('rename')->with('/test-folder', '/new-folder')->once();
    $mediaLibrary->shouldReceive('resetCache')->once();
    prepareMediaLibraryForRender($mediaLibrary);
    expect($this->mediaManagerWidget->onRenameFolder())->toBeArray()
        ->and($this->mediaManagerWidget->getSession('media_folder'))->toBe('/new-folder');
});

it('throws exception when deleting folder is disabled in onDeleteFolder', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_delete', null)->andReturnFalse();
    expect(fn() => $this->mediaManagerWidget->onDeleteFolder())->toThrow(FlashException::class);
});

it('throws exception when validation fails in onDeleteFolder', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_delete', null)->andReturnTrue();
    expect(fn() => $this->mediaManagerWidget->onDeleteFolder())->toThrow(ValidationException::class);
});

it('deletes a folder', function() {
    request()->request->add([
        'path' => '/test-folder/delete-folder',
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_delete', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder/delete-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('deleteFolder')->with('/test-folder/delete-folder')->once();
    $mediaLibrary->shouldReceive('resetCache')->once();
    prepareMediaLibraryForRender($mediaLibrary);
    expect($this->mediaManagerWidget->onDeleteFolder())->toBeArray()
        ->and($this->mediaManagerWidget->getSession('media_folder'))->toBe('/test-folder');
});

it('throws exception when renaming file is disabled in onRenameFile', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_rename', null)->andReturnFalse();
    expect(fn() => $this->mediaManagerWidget->onRenameFile())->toThrow(FlashException::class);
});

it('throws exception when validation fails in onRenameFile', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_rename', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnFalse();
    expect(fn() => $this->mediaManagerWidget->onRenameFile())->toThrow(ValidationException::class);
});

it('throws exception when file extension is not allowed in onRenameFile', function() {
    request()->request->add([
        'path' => '/test-folder',
        'file' => 'existing-file.png',
        'name' => 'new-file',
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_rename', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('isAllowedExtension')->with('png')->andReturnFalse();
    expect(fn() => $this->mediaManagerWidget->onRenameFile())
        ->toThrow(FlashException::class, lang('igniter::main.media_manager.alert_extension_not_allowed'));
});

it('throws exception when source file does not exist in onRenameFile', function() {
    request()->request->add([
        'path' => '/test-folder',
        'file' => 'not-found.png',
        'name' => 'new-file.png',
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_rename', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder/not-found.png')->andReturnFalse();
    expect(fn() => $this->mediaManagerWidget->onRenameFile())
        ->toThrow(FlashException::class, lang('igniter::main.media_manager.alert_file_not_found'));
});

it('throws exception when destination file already exist in onRenameFile', function() {
    request()->request->add([
        'path' => '/test-folder',
        'file' => 'existing-file.png',
        'name' => 'existing-file.png',
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_rename', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder/existing-file.png')->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder/existing-file.png')->andReturnTrue();
    expect(fn() => $this->mediaManagerWidget->onRenameFile())
        ->toThrow(FlashException::class, lang('igniter::main.media_manager.alert_file_exists'));
});

it('renames a file', function() {
    request()->request->add([
        'path' => '/test-folder',
        'file' => 'existing-file.png',
        'name' => 'new-file.png',
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_rename', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder/existing-file.png')->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder/new-file.png')->andReturnFalse();
    $mediaLibrary->shouldReceive('rename')->with('/test-folder/existing-file.png', '/test-folder/new-file.png')->once();
    $mediaLibrary->shouldReceive('resetCache')->once();
    prepareMediaLibraryForRender($mediaLibrary);
    expect($this->mediaManagerWidget->onRenameFile())->toBeArray();
});

it('throws exception when deleting files is disabled in onDeleteFiles', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_delete', null)->andReturnFalse();
    expect(fn() => $this->mediaManagerWidget->onDeleteFiles())->toThrow(FlashException::class);
});

it('throws exception when validation fails in onDeleteFiles', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_delete', null)->andReturnTrue();
    expect(fn() => $this->mediaManagerWidget->onDeleteFiles())->toThrow(ValidationException::class);
});

it('deletes files', function() {
    request()->request->add([
        'path' => '/test-folder',
        'files' => [
            ['path' => 'existing-file.png'],
        ],
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_delete', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('deleteFiles')->with(['/test-folder/existing-file.png'])->once();
    $mediaLibrary->shouldReceive('resetCache')->once();
    prepareMediaLibraryForRender($mediaLibrary);
    expect($this->mediaManagerWidget->onDeleteFiles())->toBeArray();
});

it('throws exception when moving files is disabled in onMoveFiles', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_move', null)->andReturnFalse();
    expect(fn() => $this->mediaManagerWidget->onMoveFiles())->toThrow(FlashException::class);
});

it('throws exception when validation fails in onMoveFiles', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_move', null)->andReturnTrue();
    expect(fn() => $this->mediaManagerWidget->onMoveFiles())->toThrow(ValidationException::class);
});

it('moves files', function() {
    request()->request->add([
        'path' => '/test-folder',
        'files' => [
            ['path' => 'file.png'],
        ],
        'destination' => '/new-folder',
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_move', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/new-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('moveFile')->with('/test-folder/file.png', '/new-folder/file.png')->once();
    $mediaLibrary->shouldReceive('resetCache')->once();
    prepareMediaLibraryForRender($mediaLibrary);
    expect($this->mediaManagerWidget->onMoveFiles())->toBeArray()
        ->and($this->mediaManagerWidget->getSession('media_folder'))->toBe('/new-folder');
});

it('throws exception when copying files is disabled in onCopyFiles', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_copy', null)->andReturnFalse();
    expect(fn() => $this->mediaManagerWidget->onCopyFiles())
        ->toThrow(FlashException::class, lang('igniter::main.media_manager.alert_copy_disabled'));
});

it('throws exception when validation fails in onCopyFiles', function() {
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_copy', null)->andReturnTrue();
    expect(fn() => $this->mediaManagerWidget->onCopyFiles())->toThrow(ValidationException::class);
});

it('copies files', function() {
    request()->request->add([
        'path' => '/test-folder',
        'files' => [
            ['path' => 'file.png'],
        ],
        'destination' => '/new-folder',
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_copy', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/new-folder')->andReturnTrue();
    $mediaLibrary->shouldReceive('copyFile')->with('/test-folder/file.png', '/new-folder/file.png')->once();
    $mediaLibrary->shouldReceive('resetCache')->once();
    prepareMediaLibraryForRender($mediaLibrary);
    expect($this->mediaManagerWidget->onCopyFiles())->toBeArray()
        ->and($this->mediaManagerWidget->getSession('media_folder'))->toBe('/new-folder');
});

it('throws exception when uploading files is disabled', function() {
    request()->headers->set('X-IGNITER-FILEUPLOAD', 'mediamanager');

    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_uploads', null)->andReturnFalse();
    expect(fn() => new MediaManager($this->controller))->toThrow(function(HttpResponseException $exception) {
        expect(json_decode($exception->getResponse()->getContent()))
            ->toContain(lang('igniter::main.media_manager.alert_upload_disabled'));
    });
});

it('throws exception when user does not have upload permission', function() {
    request()->headers->set('X-IGNITER-FILEUPLOAD', 'mediamanager');

    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_uploads', null)->andReturnTrue();
    $this->controller->setUser(User::factory()->create());

    expect(fn() => new MediaManager($this->controller))->toThrow(function(HttpResponseException $exception) {
        expect(json_decode($exception->getResponse()->getContent()))
            ->toContain(sprintf(lang('igniter::main.media_manager.alert_permission'), 'upload'));
    });
});

it('throws exception when validation fails when uploading', function() {
    request()->headers->set('X-IGNITER-FILEUPLOAD', 'mediamanager');
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_uploads', null)->andReturnTrue();
    $this->controller->setUser(User::factory()->superUser()->create());
    expect(fn() => new MediaManager($this->controller))->toThrow(HttpResponseException::class);
});

it('uploads files', function() {
    Event::fake();
    $file = UploadedFile::fake()->image('test-file.jpg');
    request()->headers->set('X-IGNITER-FILEUPLOAD', 'mediamanager');
    request()->request->add([
        'path' => '/test-folder',
        'file_data' => $file,
    ]);
    app()->instance(MediaLibrary::class, $mediaLibrary = mock(MediaLibrary::class)->makePartial());
    $mediaLibrary->shouldReceive('getConfig')->with('enable_uploads', null)->andReturnTrue();
    $mediaLibrary->shouldReceive('exists')->with('/test-folder')->andReturnTrue();
    $this->controller->setUser(User::factory()->superUser()->create());
    $fileMock = File::partialMock();
    $fileMock->shouldReceive('name')->with('test-file.png')->andReturn('test-file.png');
    $fileMock->shouldReceive('get')->andReturn('file content');
    $mediaLibrary->shouldReceive('put')->with('/test-folder/test-file.jpg', 'file content')->once();
    $mediaLibrary->shouldReceive('resetCache')->once();
    $mediaLibrary->shouldReceive('getMediaUrl')->andReturn('http://localhost/test-folder/test-file.jpg');

    expect(fn() => new MediaManager($this->controller))->toThrow(function(HttpResponseException $exception) {
        expect(json_decode($exception->getResponse()->getContent()))->toHaveKey('link', 'http://localhost/test-folder/test-file.jpg');
    });

    Event::assertDispatched('media.file.upload');
});

function prepareMediaLibraryForRender(MediaLibrary $mediaLibrary): void
{
    $mediaLibrary->shouldReceive('fetchFiles')->andReturn([]);
    $mediaLibrary->shouldReceive('folderSize')->andReturn(20324);
    $mediaLibrary->shouldReceive('listAllFolders')->andReturn([]);
    $mediaLibrary->shouldReceive('listFolders')->andReturn([]);
}
