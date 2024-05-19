<?php

namespace Igniter\Main\Widgets;

use Exception;
use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\BaseWidget;
use Igniter\Admin\Traits\ValidatesForm;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Classes\MediaLibrary;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;

/**
 * Media Manager widget.
 */
class MediaManager extends BaseWidget
{
    use ValidatesForm;

    public const ROOT_FOLDER = '/';

    /**
     * @var string Media size
     */
    public string $size = 'large';

    /**
     * Allow rows to be sorted
     * @todo Not implemented...
     */
    public bool $rowSorting = false;

    public bool $chooseButton = false;

    public string $chooseButtonText = 'igniter::main.media_manager.text_choose';

    public string $selectMode = 'multi';

    public ?string $selectItem = null;

    //
    // Object properties
    //

    protected string $defaultAlias = 'mediamanager';

    protected bool $popupLoaded = false;

    public function __construct(AdminController $controller, array $config = [])
    {
        parent::__construct($controller, $config);

        $this->checkUploadHandler();
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('mediamanager/mediamanager');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $folder = $this->getCurrentFolder();
        $sortBy = $this->getSortBy();
        $filterBy = $this->getFilterBy();
        $searchTerm = $this->getSearchTerm();

        $this->vars['currentFolder'] = $folder;
        $this->vars['isRootFolder'] = $folder == static::ROOT_FOLDER;
        $this->vars['items'] = $items = $this->listFolderItems($folder, $sortBy, ['search' => $searchTerm, 'filter' => $filterBy]);
        $this->vars['folderSize'] = $this->getCurrentFolderSize();
        $this->vars['totalItems'] = count($items);
        $this->vars['folderList'] = $this->getFolderList();
        $this->vars['folderTree'] = $this->getFolderTreeNodes();
        $this->vars['sortBy'] = $sortBy;
        $this->vars['filterBy'] = $filterBy;
        $this->vars['searchTerm'] = $searchTerm;
        $this->vars['isPopup'] = $this->popupLoaded;
        $this->vars['selectMode'] = $this->selectMode;
        $this->vars['selectItem'] = $this->selectItem;
        $this->vars['maxUploadSize'] = round($this->getSetting('max_upload_size', 1500) / 1024, 2);
        $this->vars['allowedExtensions'] = $this->getMediaLibrary()->getAllowedExtensions();
        $this->vars['chooseButton'] = $this->chooseButton;
        $this->vars['chooseButtonText'] = $this->chooseButtonText;
        $this->vars['breadcrumbs'] = $this->makeBreadcrumb();
    }

    public function loadAssets()
    {
        $this->addCss('mediamanager.css', 'mediamanager-css');

        $this->addJs('mediamanager.js', 'mediamanager-js');
        $this->addJs('mediamanager.modal.js', 'mediamanager-modal-js');
    }

    public function getSetting(string $name, mixed $default = null): mixed
    {
        return $this->getMediaLibrary()->getConfig($name, $default);
    }

    //
    // Event handlers
    //

    public function onSetSorting(): array
    {
        $this->setSortBy(input('sortBy'));
        $this->setCurrentFolder(input('path'));

        $this->prepareVars();

        return [
            '#'.$this->getId('item-list') => $this->makePartial('mediamanager/item_list'),
            '#'.$this->getId('toolbar') => $this->makePartial('mediamanager/toolbar'),
        ];
    }

    public function onSetFilter(): array
    {
        $filterBy = input('filterBy');

        $this->setFilterBy($filterBy);

        $this->prepareVars();

        return [
            '#'.$this->getId('item-list') => $this->makePartial('mediamanager/item_list'),
            '#'.$this->getId('toolbar') => $this->makePartial('mediamanager/toolbar'),
        ];
    }

    public function onSearch(): array
    {
        $search = input('search');
        $this->setSearchTerm($search);

        $this->prepareVars();

        return [
            '#'.$this->getId('item-list') => $this->makePartial('mediamanager/item_list'),
        ];
    }

    public function onGoToFolder(): array
    {
        throw_unless($this->getMediaLibrary()->exists($path = post('path')), new FlashException(
            lang('igniter::main.media_manager.alert_invalid_path')
        ));

        if (post('resetCache')) {
            $this->getMediaLibrary()->resetCache();
        }

        if (post('resetSearch')) {
            $this->setSearchTerm('');
        }

        $this->setCurrentFolder($path);
        $this->prepareVars();

        return [
            '#'.$this->getId('item-list') => $this->makePartial('mediamanager/item_list'),
            '#'.$this->getId('folder-tree') => $this->makePartial('mediamanager/folder_tree'),
            '#'.$this->getId('breadcrumb') => $this->makePartial('mediamanager/breadcrumb'),
            '#'.$this->getId('statusbar') => $this->makePartial('mediamanager/statusbar'),
        ];
    }

    public function onLoadPopup(): string
    {
        $this->popupLoaded = true;
        $this->selectMode = post('selectMode');
        $this->chooseButton = post('chooseButton');
        $this->chooseButtonText = post('chooseButtonText', $this->chooseButtonText);

        $goToItem = post('goToItem');
        if ($goToPath = dirname($goToItem)) {
            $this->selectItem = basename($goToItem);
            $this->setCurrentFolder($goToPath);
        }

        return $this->makePartial('mediamanager/popup', ['_mediamanager' => $this]);
    }

    public function onCreateFolder(): array
    {
        $mediaLibrary = $this->getMediaLibrary();

        throw_unless($this->getSetting('enable_new_folder'), new FlashException(
            lang('igniter::main.media_manager.alert_new_folder_disabled')
        ));

        $validated = $this->validate(post(), [
            'path' => ['string', 'starts_with:'.DIRECTORY_SEPARATOR, $this->validateFileExists()],
            'name' => ['filled', 'regex:/^[0-9a-z@\.\s_\-]+$/i', 'not_regex:(\.\.)'],
        ], [
            'starts_with' => lang('igniter::main.media_manager.alert_invalid_path'),
            'regex' => lang('igniter::main.media_manager.alert_invalid_file_name'),
            'name.filled' => lang('igniter::main.media_manager.alert_file_name_required'),
        ]);

        $path = trim(array_get($validated, 'path'));
        $fullPath = $path.'/'.trim(array_get($validated, 'name'));

        throw_if($mediaLibrary->exists($fullPath), new FlashException(
            lang('igniter::main.media_manager.alert_file_exists')
        ));

        $mediaLibrary->makeFolder($fullPath);
        $mediaLibrary->resetCache();
        $this->setCurrentFolder($fullPath);

        $this->prepareVars();

        return [
            '#'.$this->getId('item-list') => $this->makePartial('mediamanager/item_list'),
            '#'.$this->getId('folder-tree') => $this->makePartial('mediamanager/folder_tree'),
            '#'.$this->getId('breadcrumb') => $this->makePartial('mediamanager/breadcrumb'),
            '#'.$this->getId('statusbar') => $this->makePartial('mediamanager/statusbar'),
        ];
    }

    public function onRenameFolder(): array
    {
        $mediaLibrary = $this->getMediaLibrary();

        throw_unless($this->getSetting('enable_rename'), new FlashException(
            lang('igniter::main.media_manager.alert_rename_disabled')
        ));

        $validated = $this->validate(post(), [
            'path' => ['string', 'starts_with:'.DIRECTORY_SEPARATOR, $this->validateFileExists()],
            'name' => ['filled', 'regex:/^[0-9a-z@\.\s_\-]+$/i', 'not_regex:(\.\.)'],
        ], [
            'starts_with' => lang('igniter::main.media_manager.alert_invalid_path'),
            'regex' => lang('igniter::main.media_manager.alert_invalid_file_name'),
            'name.filled' => lang('igniter::main.media_manager.alert_file_name_required'),
        ]);

        $path = trim(array_get($validated, 'path'));
        $newPath = File::dirname($path).'/'.trim(array_get($validated, 'name'));

        throw_if($mediaLibrary->exists($newPath), new FlashException(
            lang('igniter::main.media_manager.alert_file_exists')
        ));

        $mediaLibrary->rename($path, $newPath);
        $mediaLibrary->resetCache();
        $this->setCurrentFolder($newPath);

        $this->prepareVars();

        return [
            '#'.$this->getId('item-list') => $this->makePartial('mediamanager/item_list'),
            '#'.$this->getId('folder-tree') => $this->makePartial('mediamanager/folder_tree'),
            '#'.$this->getId('breadcrumb') => $this->makePartial('mediamanager/breadcrumb'),
        ];
    }

    public function onRenameFile(): array
    {
        $mediaLibrary = $this->getMediaLibrary();

        throw_unless($this->getSetting('enable_rename'), new FlashException(
            lang('igniter::main.media_manager.alert_rename_disabled')
        ));

        $validated = $this->validate(post(), [
            'path' => ['string', 'starts_with:'.DIRECTORY_SEPARATOR, $this->validateFileExists()],
            'file' => ['filled', 'regex:/^[0-9a-z@\.\s_\-]+$/i', 'not_regex:(\.\.)', 'ends_with:'.implode(',', array_map(fn($value) => '.'.$value, $mediaLibrary->getAllowedExtensions()))],
            'name' => ['filled', 'regex:/^[0-9a-z@\.\s_\-]+$/i', 'not_regex:(\.\.)'],
        ], [
            'starts_with' => lang('igniter::main.media_manager.alert_invalid_path'),
            'extensions' => lang('igniter::main.media_manager.alert_extension_not_allowed'),
            'regex' => lang('igniter::main.media_manager.alert_invalid_file_name'),
            'file.filled' => lang('igniter::main.media_manager.alert_file_name_required'),
            'name.filled' => lang('igniter::main.media_manager.alert_invalid_new_file_name'),
        ]);

        $path = trim(array_get($validated, 'path'));
        $oldPath = $path.'/'.trim(array_get($validated, 'file'));
        $newPath = $path.'/'.trim(array_get($validated, 'name'));

        if (!File::extension($newPath)) {
            $newPath .= '.'.File::extension($oldPath);
        }

        throw_unless($mediaLibrary->isAllowedExtension(File::extension($newPath)), new FlashException(
            lang('igniter::main.media_manager.alert_extension_not_allowed')
        ));

        throw_unless($mediaLibrary->exists($oldPath), new FlashException(
            lang('igniter::main.media_manager.alert_file_not_found')
        ));

        throw_if($mediaLibrary->exists($newPath), new FlashException(
            lang('igniter::main.media_manager.alert_file_exists')
        ));

        $mediaLibrary->rename($oldPath, $newPath);
        $mediaLibrary->resetCache();

        $this->prepareVars();

        return [
            '#'.$this->getId('item-list') => $this->makePartial('mediamanager/item_list'),
            '#'.$this->getId('folder-tree') => $this->makePartial('mediamanager/folder_tree'),
            '#'.$this->getId('breadcrumb') => $this->makePartial('mediamanager/breadcrumb'),
            '#'.$this->getId('statusbar') => $this->makePartial('mediamanager/statusbar'),
        ];
    }

    public function onDeleteFolder(): array
    {
        $mediaLibrary = $this->getMediaLibrary();

        throw_unless($this->getSetting('enable_delete'), new FlashException(
            lang('igniter::main.media_manager.alert_delete_disabled')
        ));

        $validated = $this->validate(post(), [
            'path' => ['string', 'starts_with:'.DIRECTORY_SEPARATOR, $this->validateFileExists()],
        ], [
            'starts_with' => lang('igniter::main.media_manager.alert_invalid_path'),
        ]);

        $path = trim(array_get($validated, 'path'));
        $mediaLibrary->deleteFolder($path);
        $mediaLibrary->resetCache();
        $this->setCurrentFolder(dirname($path));

        $this->prepareVars();

        return [
            '#'.$this->getId('item-list') => $this->makePartial('mediamanager/item_list'),
            '#'.$this->getId('folder-tree') => $this->makePartial('mediamanager/folder_tree'),
            '#'.$this->getId('breadcrumb') => $this->makePartial('mediamanager/breadcrumb'),
            '#'.$this->getId('statusbar') => $this->makePartial('mediamanager/statusbar'),
        ];
    }

    public function onDeleteFiles(): array
    {
        $mediaLibrary = $this->getMediaLibrary();

        throw_unless($this->getSetting('enable_delete'), new FlashException(
            lang('igniter::main.media_manager.alert_delete_disabled')
        ));

        $validated = $this->validate(post(), [
            'path' => ['string', 'starts_with:'.DIRECTORY_SEPARATOR, $this->validateFileExists()],
            'files' => ['filled', 'array'],
            'files.*.path' => ['regex:/^[0-9a-z@\.\s_\-]+$/i', 'not_regex:(\.\.)'],
        ], [
            'starts_with' => lang('igniter::main.media_manager.alert_invalid_path'),
            'array' => lang('igniter::main.media_manager.alert_select_delete_file'),
            'regex' => lang('igniter::main.media_manager.alert_invalid_file_name'),
        ]);

        $path = trim(array_get($validated, 'path'));
        $files = array_map(function($value) use ($path) {
            return $path.'/'.$value['path'];
        }, array_get($validated, 'files'));

        $mediaLibrary->deleteFiles($files);
        $mediaLibrary->resetCache();

        $this->prepareVars();

        return [
            '#'.$this->getId('item-list') => $this->makePartial('mediamanager/item_list'),
            '#'.$this->getId('folder-tree') => $this->makePartial('mediamanager/folder_tree'),
            '#'.$this->getId('breadcrumb') => $this->makePartial('mediamanager/breadcrumb'),
            '#'.$this->getId('statusbar') => $this->makePartial('mediamanager/statusbar'),
        ];
    }

    public function onMoveFiles(): array
    {
        $mediaLibrary = $this->getMediaLibrary();

        throw_unless($this->getSetting('enable_move'), new FlashException(
            lang('igniter::main.media_manager.alert_move_disabled')
        ));

        $validated = $this->validate(post(), [
            'path' => ['string', 'starts_with:'.DIRECTORY_SEPARATOR, $this->validateFileExists()],
            'files' => ['filled', 'array'],
            'files.*.path' => ['regex:/^[0-9a-z@\.\s_\-]+$/i', 'not_regex:(\.\.)'],
            'destination' => ['string', $this->validateFileExists()],
        ], [
            'starts_with' => lang('igniter::main.media_manager.alert_invalid_path'),
            'array' => lang('igniter::main.media_manager.alert_select_delete_file'),
            'regex' => lang('igniter::main.media_manager.alert_invalid_file_name'),
        ]);

        $source = trim(array_get($validated, 'path'));
        $destination = trim(array_get($validated, 'destination'));

        foreach (array_get($validated, 'files') as $file) {
            $name = $file['path'];
            $mediaLibrary->moveFile($source.'/'.$name, $destination.'/'.$name);
        }

        $mediaLibrary->resetCache();
        $this->setCurrentFolder($destination);

        $this->prepareVars();

        return [
            '#'.$this->getId('item-list') => $this->makePartial('mediamanager/item_list'),
            '#'.$this->getId('folder-tree') => $this->makePartial('mediamanager/folder_tree'),
            '#'.$this->getId('breadcrumb') => $this->makePartial('mediamanager/breadcrumb'),
            '#'.$this->getId('statusbar') => $this->makePartial('mediamanager/statusbar'),
        ];
    }

    public function onCopyFiles(): array
    {
        $mediaLibrary = $this->getMediaLibrary();

        throw_unless($this->getSetting('enable_copy'), new FlashException(
            lang('igniter::main.media_manager.alert_copy_disabled')
        ));

        $validated = $this->validate(post(), [
            'path' => ['string', 'starts_with:'.DIRECTORY_SEPARATOR, $this->validateFileExists()],
            'files' => ['filled', 'array'],
            'files.*.path' => ['regex:/^[0-9a-z@\.\s_\-]+$/i', 'not_regex:(\.\.)'],
            'destination' => ['string', $this->validateFileExists()],
        ], [
            'starts_with' => lang('igniter::main.media_manager.alert_invalid_path'),
            'array' => lang('igniter::main.media_manager.alert_select_delete_file'),
            'regex' => lang('igniter::main.media_manager.alert_invalid_file_name'),
        ]);

        $source = trim(array_get($validated, 'path'));
        $destination = trim(array_get($validated, 'destination'));

        foreach (array_get($validated, 'files') as $file) {
            $name = $file['path'];
            $mediaLibrary->copyFile($source.'/'.$name, $destination.'/'.$name);
        }

        $mediaLibrary->resetCache();
        $this->setCurrentFolder($destination);

        $this->prepareVars();

        return [
            '#'.$this->getId('item-list') => $this->makePartial('mediamanager/item_list'),
            '#'.$this->getId('folder-tree') => $this->makePartial('mediamanager/folder_tree'),
            '#'.$this->getId('breadcrumb') => $this->makePartial('mediamanager/breadcrumb'),
            '#'.$this->getId('statusbar') => $this->makePartial('mediamanager/statusbar'),
        ];
    }

    //
    // Methods for internal use
    //

    protected function getMediaLibrary(): MediaLibrary
    {
        return resolve(MediaLibrary::class);
    }

    protected function listFolderItems(string $folder, array $sortBy, null|string|array $filter): array
    {
        return $this->getMediaLibrary()->fetchFiles($folder, $sortBy, $filter);
    }

    protected function getFolderList(): array
    {
        $result = [];

        $currentFolder = $this->getCurrentFolder();
        $folderList = $this->getMediaLibrary()->listAllFolders();

        foreach ($folderList as $value) {
            if ($value == $currentFolder) {
                continue;
            }

            $result[$value] = $value;
        }

        return $result;
    }

    protected function getFolderTreeNodes(): array
    {

        $mediaLibrary = $this->getMediaLibrary();

        $folderTree = function($path) use (&$folderTree, $mediaLibrary) {
            $result = [];
            if (!($folders = $mediaLibrary->listFolders($path))) {
                return null;
            }

            foreach ($folders as $folder) {
                $node = [];
                $node['text'] = $folder;
                $node['path'] = $folder;

                $node['state']['expanded'] = $this->isFolderTreeNodeExpanded($folder);
                $node['state']['selected'] = $this->isFolderTreeNodeSelected($folder);

                $node['nodes'] = ($folder != static::ROOT_FOLDER)
                    ? $folderTree($folder)
                    : null;

                $result[] = $node;
            }

            return $result;
        };

        return $folderTree(static::ROOT_FOLDER);
    }

    protected function getCurrentFolderSize(): string
    {
        return $this->makeReadableSize($this->getMediaLibrary()->folderSize($this->getCurrentFolder()));
    }

    protected function setCurrentFolder(string $path)
    {
        $path = $this->getMediaLibrary()->validatePath($path);
        $this->putSession('media_folder', $path);
    }

    protected function getCurrentFolder(): string
    {
        return $this->getSession('media_folder', static::ROOT_FOLDER);
    }

    protected function setSearchTerm(string $searchTerm)
    {
        $this->putSession('media_search', trim($searchTerm));
    }

    protected function getSearchTerm(): string
    {
        return $this->getSession('media_search', '');
    }

    protected function setSortBy(string $sortBy)
    {
        $sort = $this->getSortBy();
        $direction = 'descending';
        if ($sort && in_array($direction, $sort)) {
            $direction = 'ascending';
        }

        $sortBy = [$sortBy, $direction];

        $this->putSession('media_sort_by', $sortBy);
    }

    protected function getSortBy(): array
    {
        return $this->getSession('media_sort_by', ['name', 'ascending']);
    }

    protected function setFilterBy(string $filterBy)
    {
        $this->putSession('media_filter_by', $filterBy);
    }

    protected function getFilterBy(): string
    {
        return $this->getSession('media_filter_by', 'all');
    }

    protected function checkUploadHandler()
    {
        if (!($uniqueId = Request::header('X-IGNITER-FILEUPLOAD')) || $uniqueId != $this->getId()) {
            return;
        }

        $mediaLibrary = $this->getMediaLibrary();

        try {
            if (!$this->getSetting('enable_uploads')) {
                throw new FlashException(lang('igniter::main.media_manager.alert_upload_disabled'));
            }

            if (!$this->controller->getUser()->hasPermission('Admin.MediaManager')) {
                throw new FlashException(sprintf(lang('igniter::main.media_manager.alert_permission'), 'upload'));
            }

            $this->validate(post(), [
                'path' => ['string', 'starts_with:'.DIRECTORY_SEPARATOR, $this->validateFileExists()],
                'file_data' => ['file', 'mimes:'.implode(',', $mediaLibrary->getAllowedExtensions())],
            ], [
                'starts_with' => lang('igniter::main.media_manager.alert_invalid_path'),
                'mimes' => lang('igniter::main.media_manager.alert_extension_not_allowed'),
                'file' => lang('igniter::main.media_manager.alert_file_not_found'),
            ]);

            $uploadedFile = Request::file('file_data');
            $fileName = $uploadedFile->getClientOriginalName();
            $path = Request::get('path');

            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            $fileName = File::name($fileName).'.'.$extension;
            $filePath = $path.'/'.$fileName;

            $mediaLibrary->put(
                $filePath,
                File::get($uploadedFile->getRealPath())
            );

            $mediaLibrary->resetCache();

            $this->fireSystemEvent('media.file.upload', [$filePath, $uploadedFile]);

            Response::json([
                'link' => $mediaLibrary->getMediaUrl($filePath),
                'result' => 'success',
            ])->send();
        } catch (Exception $ex) {
            Response::json($ex->getMessage(), 400)->send();
        }

        exit;
    }

    protected function validateFileExists(): \Closure
    {
        return function(string $attribute, mixed $value, \Closure $fail) {
            if ($value === 'foo') {
                $fail("The $attribute file/folder does not exists.");
            }
        };
    }

    protected function makeBreadcrumb(): array
    {
        $result = [];

        $folder = $this->getCurrentFolder();
        if ($folderArray = explode('/', $folder)) {
            $tmpPath = '';
            $result[] = ['name' => '<i class="fa fa-home"></i>'];
            foreach ($folderArray as $p_dir) {
                $tmpPath .= $p_dir.'/';
                if ($p_dir != '') {
                    $result[] = ['name' => $p_dir, 'link' => $tmpPath];
                }
            }
        }

        return $result;
    }

    protected function makeReadableSize(mixed $size): string
    {
        if (!$size) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $u = 0;
        while ((round($size / 1024) > 0) && ($u < 4)) {
            $size /= 1024;
            $u++;
        }

        return number_format($size).' '.$units[$u];
    }

    protected function isFolderTreeNodeExpanded(string $node): bool
    {
        return starts_with($this->getCurrentFolder(), $node);
    }

    protected function isFolderTreeNodeSelected(string $node): bool
    {
        return $this->getCurrentFolder() == $node;
    }
}
