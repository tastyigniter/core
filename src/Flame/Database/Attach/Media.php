<?php

namespace Igniter\Flame\Database\Attach;

use FilesystemIterator;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 *
 *
 * @property int $id
 * @property string $disk
 * @property string $name
 * @property string $file_name
 * @property string $mime_type
 * @property int $size
 * @property string|null $tag
 * @property string|null $attachment_type
 * @property int|null $attachment_id
 * @property int $is_public
 * @property array|null $custom_properties
 * @property int|null $priority
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $attachment
 * @property-read mixed $extension
 * @property-read string $height
 * @property-read mixed $human_readable_size
 * @property-read string $path
 * @property-read string $type
 * @property-read string $width
 * @method static \Igniter\Flame\Database\Builder<static>|Media applyFilters(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Media applySorts(array $sorts = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Media dropdown(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|Media like(string $column, string $value, string $side = 'both', string $boolean = 'and')
 * @method static \Igniter\Flame\Database\Builder<static>|Media listFrontEnd(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Media lists(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|Media newModelQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|Media newQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|Media orLike(string $column, string $value, string $side = 'both')
 * @method static \Igniter\Flame\Database\Builder<static>|Media orSearch(string $term, string $columns = [], string $mode = 'all')
 * @method static array pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @method static \Igniter\Flame\Database\Builder<static>|Media query()
 * @method static \Igniter\Flame\Database\Builder<static>|Media search(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|Media sorted()
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereAttachmentId($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereAttachmentType($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereCreatedAt($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereCustomProperties($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereDisk($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereFileName($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereId($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereIsPublic($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereMimeType($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereName($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media wherePriority($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereSize($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereTag($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Media whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Media extends Model
{
    use \Igniter\Flame\Database\Traits\Sortable;

    const SORT_ORDER = 'priority';

    protected $table = 'media_attachments';

    public $timestamps = true;

    protected $guarded = ['disk'];

    /**
     * @var array Known image extensions.
     */
    public static $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * @var array Hidden fields from array/json access
     */
    protected $hidden = ['attachment_type', 'attachment_id', 'is_public'];

    /**
     * @var array Add fields to array/json access
     */
    protected $appends = ['path', 'extension'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'manipulations' => 'array',
        'custom_properties' => 'array',
    ];

    /**
     * @var array Mime types
     */
    protected $autoMimeTypes = [
        'gif' => 'image/gif',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'docx' => 'application/msword',
        'xlsx' => 'application/excel',
    ];

    public $fileToAdd;

    /**
     * Set the polymorphic relation.
     *
     * @return mixed
     */
    public function attachment()
    {
        return $this->morphTo('attachment');
    }

    /**
     * Creates a file object from a file an uploaded file.
     * @return self
     */
    public function addFromRequest(UploadedFile $uploadedFile, $tag = null)
    {
        $this->getMediaAdder()
            ->performedOn($this->attachment)
            ->useMediaTag($tag)
            ->fromFile($uploadedFile);

        return $this;
    }

    /**
     * Creates a file object from a file on the disk.
     * @return self|void
     */
    public function addFromFile($filePath, $tag = null)
    {
        if (is_null($filePath)) {
            return;
        }

        $this->getMediaAdder()
            ->performedOn($this->attachment)
            ->useMediaTag($tag)
            ->fromFile(new SymfonyFile($filePath));

        return $this;
    }

    /**
     * Creates a file object from raw data.
     *
     * @param $rawData string Raw data
     * @param $filename string Filename
     *
     * @return $this|void
     */
    public function addFromRaw($rawData, $filename, $tag = null)
    {
        if (is_null($rawData)) {
            return;
        }

        $tempPath = $this->getTempPath().$filename;
        if (!File::isDirectory(dirname($tempPath))) {
            File::makeDirectory(dirname($tempPath), 775, true);
        }

        File::put($tempPath, $rawData);

        $this->addFromFile($tempPath, $tag);
        File::delete($tempPath);

        return $this;
    }

    /**
     * Creates a file object from url
     * @param $url string URL
     * @param $filename string Filename
     * @return $this
     * @throws \Exception
     */
    public function addFromUrl($url, $filename = null, $tag = null)
    {
        if (!$stream = @fopen($url, 'rb')) {
            throw new \RuntimeException(sprintf('Error opening file "%s"', $url));
        }

        return $this->addFromRaw(
            $stream,
            !empty($filename) ? $filename : File::basename($url),
            $tag,
        );
    }

    //
    // Attribute mutators
    //

    /**
     * Helper attribute for getPath.
     * @return string
     */
    public function getPathAttribute()
    {
        return $this->getPath();
    }

    /**
     * Determine the type of a file.
     *
     * @return string
     */
    public function getTypeAttribute()
    {
        return $this->getMimeType();
    }

    public function getExtensionAttribute()
    {
        return $this->getExtension();
    }

    /**
     * Helper attribute for get image width.
     * @return string
     */
    public function getWidthAttribute()
    {
        if ($this->isImage()) {
            $dimensions = $this->getImageDimensions();

            return $dimensions[0];
        }
    }

    /**
     * Helper attribute for get image height.
     * @return string
     */
    public function getHeightAttribute()
    {
        if ($this->isImage()) {
            $dimensions = $this->getImageDimensions();

            return $dimensions[1];
        }
    }

    public function getHumanReadableSizeAttribute()
    {
        return $this->sizeToString($this->size);
    }

    //
    // Getters
    //

    /**
     * Returns the file name without path
     */
    public function getFilename()
    {
        return $this->file_name;
    }

    /**
     * Returns the file extension.
     */
    public function getExtension()
    {
        return File::extension($this->file_name);
    }

    /**
     * Returns the last modification date as a UNIX timestamp.
     * @return int
     */
    public function getLastModified($fileName = null)
    {
        if (!$fileName) {
            $fileName = $this->disk;
        }

        return $this->getStorageDisk()->lastModified($this->getStoragePath().$fileName);
    }

    /**
     * Returns the public address to access the file.
     */
    public function getPath()
    {
        return $this->getPublicPath().$this->getPartitionDirectory().$this->name;
    }

    /**
     * Returns a local path to this file. If the file is stored remotely,
     * it will be downloaded to a temporary directory.
     */
    public function getFullDiskPath()
    {
        return $this->getStorageDisk()->path($this->getDiskPath());
    }

    /**
     * Returns the path to the file, relative to the storage disk.
     * @return string
     */
    public function getDiskPath()
    {
        return $this->getStoragePath().$this->name;
    }

    /**
     * Determines if the file is flagged "public" or not.
     */
    public function isPublic()
    {
        if (is_null($this->is_public)) {
            return true;
        }

        return $this->is_public;
    }

    /**
     * Returns the file size as string.
     * @return string Returns the size as string.
     */
    public function sizeToString()
    {
        return File::sizeToString($this->file_size);
    }

    public function getMimeType()
    {
        if (!is_null($this->mime_type)) {
            return $this->mime_type;
        }

        if ($type = $this->getTypeFromExtension()) {
            return $this->mime_type = $type;
        }

        return null;
    }

    public function getTypeFromExtension()
    {
        $ext = $this->getExtension();
        if (isset($this->autoMimeTypes[$ext])) {
            return $this->autoMimeTypes[$ext];
        }
    }

    /**
     * Generates a unique name from the supplied file name.
     */
    public function getUniqueName()
    {
        if (!is_null($this->name)) {
            return $this->name;
        }

        $ext = strtolower($this->getExtension());

        $name = str_replace('.', '', uniqid(null, true));

        return $this->name = $name.(strlen($ext) ? '.'.$ext : '');
    }

    public function getDiskName()
    {
        if (!is_null($this->disk)) {
            return $this->disk;
        }

        $diskName = config('igniter-system.assets.attachment.disk');
        if (is_null(config("filesystems.disks.{$diskName}"))) {
            throw new \LogicException("There is no filesystem disk named '{$diskName}''");
        }

        return $this->disk = $diskName;
    }

    public function getDiskDriverName()
    {
        return strtolower(config("filesystems.disks.{$this->disk}.driver"));
    }

    //
    //
    //

    /**
     * Delete all thumbnails for this file.
     */
    public function deleteThumbs()
    {
        $pattern = 'thumb_'.$this->id.'_';

        $directory = $this->getStoragePath();
        $allFiles = $this->getStorageDisk()->files($directory);
        $paths = array_filter($allFiles, function($file) use ($pattern) {
            return starts_with(basename($file), $pattern);
        });

        $this->getStorageDisk()->delete($paths);
    }

    /**
     * Delete file contents from storage device.
     * @return void
     */
    public function deleteFile($fileName = null)
    {
        if (!$fileName) {
            $fileName = $this->name;
        }

        $directory = $this->getStoragePath();
        $filePath = $directory.$fileName;

        if ($this->getStorageDisk()->exists($filePath)) {
            $this->getStorageDisk()->delete($filePath);
        }

        $this->deleteEmptyDirectory($directory);
    }

    /**
     * Checks if directory is empty then deletes it,
     * three levels up to match the partition directory.
     * @param string $directory
     * @return void
     */
    protected function deleteEmptyDirectory($directory = null)
    {
        if (!$this->isDirectoryEmpty($directory)) {
            return;
        }

        $this->getStorageDisk()->deleteDirectory($directory);

        $directory = dirname($directory);
        if (!$this->isDirectoryEmpty($directory)) {
            return;
        }

        $this->getStorageDisk()->deleteDirectory($directory);

        $directory = dirname($directory);
        if (!$this->isDirectoryEmpty($directory)) {
            return;
        }

        $this->getStorageDisk()->deleteDirectory($directory);
    }

    /**
     * Returns true if a directory contains no files.
     * @return bool|null
     */
    protected function isDirectoryEmpty($directory)
    {
        $path = $this->getStorageDisk()->path($directory);

        return !(new FilesystemIterator($path))->valid();
    }

    /**
     * Check file exists on storage device.
     * @param string $fileName
     * @return bool
     */
    protected function hasFile($fileName = null)
    {
        $filePath = $this->getStoragePath().$fileName;

        return $this->getStorageDisk()->exists($filePath);
    }

    //
    // Image handling
    //

    /**
     * Checks if the file extension is an image and returns true or false.
     */
    public function isImage()
    {
        return in_array(strtolower($this->getExtension()), static::$imageExtensions);
    }

    /**
     * Generates and returns a thumbnail url.
     * @param int $width
     * @param int $height
     * @param array $options
     * @return string
     */
    public function getThumb($options = [])
    {
        if (!$this->isImage()) {
            return $this->getPath();
        }

        $options = $this->getDefaultThumbOptions($options);

        $thumbFile = $this->getThumbFilename($options);
        if (!$this->hasFile($thumbFile)) {
            $this->makeThumb($thumbFile, $options);
        }

        return $this->getPublicPath().$this->getPartitionDirectory().$thumbFile;
    }

    public function outputThumb($options = []) {}

    public function getDefaultThumbPath($thumbPath, $default = null)
    {
        if (!$default) {
            $this->getStorageDisk()->put($thumbPath, Manipulator::decodedBlankImage());
            $default = $thumbPath;
        }

        return $this->getStorageDisk()->path($default);
    }

    /**
     * Get image dimensions
     * @return array|bool
     */
    protected function getImageDimensions()
    {
        return getimagesize($this->getFullDiskPath());
    }

    /**
     * Generates a thumbnail filename.
     * @param int $width
     * @param int $height
     * @param array $options
     * @return string
     */
    protected function getThumbFilename($options)
    {
        return 'thumb_'
            .$this->id.'_'
            .$options['width'].'_'.$options['height'].'_'.$options['fit'].'_'
            .substr(md5(serialize(array_except($options, ['width', 'height', 'fit']))), 0, 8).
            '.'.$options['extension'];
    }

    /**
     * Returns the default thumbnail options.
     * @param array $override
     * @return array
     */
    protected function getDefaultThumbOptions($override = [])
    {
        $defaultOptions = [
            'fit' => 'contain',
            'width' => 0,
            'height' => 0,
            'quality' => 90,
            'sharpen' => 0,
            'extension' => 'auto',
        ];

        if (!is_array($override)) {
            $override = ['fit' => $override];
        }

        $options = array_merge($defaultOptions, $override);

        if (strtolower($options['extension']) == 'auto') {
            $options['extension'] = strtolower($this->getExtension());
        }

        return $options;
    }

    /**
     * Generate the thumbnail
     * @param array $options
     */
    protected function makeThumb($thumbFile, $options)
    {
        $thumbFile = $this->getStoragePath().$thumbFile;
        $filePath = $this->getDiskPath();

        if (!$this->hasFile($this->name)) {
            $filePath = $this->getDefaultThumbPath($thumbFile, array_get($options, 'default'));
        }

        $manipulator = Manipulator::make($filePath)->useSource(
            $this->getStorageDisk(),
        );

        if ($manipulator->isSupported()) {
            $manipulator->manipulate(array_except($options, ['extension', 'default']));
        }

        $manipulator->save($thumbFile);
    }

    //
    // Custom Properties
    //

    public function getCustomProperties()
    {
        return $this->custom_properties;
    }

    /*
     * Determine if the media item has a custom property with the given name.
     */
    public function hasCustomProperty($propertyName)
    {
        return array_has($this->custom_properties, $propertyName);
    }

    /**
     * Get if the value of custom property with the given name.
     *
     * @param string $propertyName
     * @param mixed $default
     *
     * @return mixed
     */
    public function getCustomProperty($propertyName, $default = null)
    {
        return array_get($this->custom_properties, $propertyName, $default);
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setCustomProperty($name, $value)
    {
        $customProperties = $this->custom_properties;

        array_set($customProperties, $name, $value);

        $this->custom_properties = $customProperties;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function forgetCustomProperty($name)
    {
        $customProperties = $this->custom_properties;

        array_forget($customProperties, $name);

        $this->custom_properties = $customProperties;

        return $this;
    }

    //
    // Configuration
    //

    /**
     * Define the internal storage path, override this method to define.
     */
    public function getStoragePath()
    {
        return $this->getStorageDirectory().$this->getPartitionDirectory();
    }

    /**
     * Define the public address for the storage path.
     */
    public function getPublicPath()
    {
        return $this->getStorageDisk()->url($this->getStorageDirectory());
    }

    /**
     * Define the internal working path, override this method to define.
     */
    public function getTempPath()
    {
        $path = temp_path().'/attachments/';

        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0777, true, true);
        }

        return $path;
    }

    /**
     * Define the internal storage folder, override this method to define.
     */
    public function getStorageDirectory()
    {
        $mediaFolder = config('igniter-system.assets.attachment.folder', 'media/attachments/');

        return $this->isPublic() ? $mediaFolder.'public/' : $mediaFolder.'protected/';
    }

    /**
     * Generates a partition for the file.
     * @return mixed
     */
    public function getPartitionDirectory()
    {
        return implode('/', array_slice(str_split($this->name, 3), 0, 3)).'/';
    }

    /**
     * @return \Illuminate\Filesystem\FilesystemAdapter
     * @throws \Exception
     */
    protected function getStorageDisk()
    {
        return Storage::disk($this->getDiskName());
    }

    protected function getMediaAdder()
    {
        return app(MediaAdder::class)->on($this);
    }
}
