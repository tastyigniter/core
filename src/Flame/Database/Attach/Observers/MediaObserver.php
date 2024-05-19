<?php

namespace Igniter\Flame\Database\Attach\Observers;

use Igniter\Flame\Database\Attach\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaObserver
{
    public function saved(Media $media)
    {
        if (!is_null($media->fileToAdd)) {
            if ($media->fileToAdd instanceof UploadedFile) {
                $media->addFromRequest($media->fileToAdd);
            } else {
                $media->addFromFile($media->fileToAdd);
            }

            $media->fileToAdd = null;
        }
    }

    public function deleted(Media $media)
    {
        rescue(function() use ($media) {
            $media->deleteThumbs();
            $media->deleteFile();
        });
    }
}