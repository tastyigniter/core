<?php

namespace Igniter\System\Database\Migrations;

use Igniter\Admin\Models\Category;
use Igniter\Admin\Models\Location;
use Igniter\Admin\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Igniter\Main\Classes\MediaLibrary;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('media_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('disk');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type');
            $table->integer('size')->unsigned();
            $table->string('tag')->index()->nullable();
            $table->nullableMorphs('attachment');
            $table->boolean('is_public')->default(1);
            $table->text('custom_properties')->nullable();
            $table->integer('priority')->unsigned()->nullable();
            $table->nullableTimestamps();
        });

        $this->seedAttachmentsFromExistingModels();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('media_attachments');
    }

    protected function seedAttachmentsFromExistingModels()
    {
        Menu::select('menu_photo', 'menu_id')->get()->each(function ($model) {
            if (!empty($model->menu_photo))
                $this->createMediaAttachment($model->menu_photo, $model, 'thumb');
        });

        Category::pluck('image', 'category_id')->each(function ($model) {
            if (!empty($model->image))
                $this->createMediaAttachment($model->image, $model, 'thumb');
        });

        Location::select('location_image', 'options', 'location_id')->get()->each(function ($model) {
            if (!empty($model->location_image)) {
                $this->createMediaAttachment($model->location_image, $model, 'thumb');
            }

            if (!empty($images = array_get($model->options, 'gallery.images'))) {
                foreach ($images as $image)
                    $this->createMediaAttachment($image, $model, 'gallery');
            }
        });
    }

    protected function createMediaAttachment($path, $model, $tagName)
    {
        try {
            $mediaLibrary = MediaLibrary::instance();
            $path = $mediaLibrary->getMediaRelativePath($path);

            $media = $model->newMediaInstance();
            $media->addFromFile(assets_path($mediaLibrary->getMediaPath($path)), $tagName);

            $media->save();
            $model->media()->save($media);
        }
        catch (\Exception $ex) {
            Log::debug($ex);
        }
    }
};