<?php

namespace Igniter\Flame\Composer;

use Composer\Config;
use Composer\Downloader\DownloadManager;
use Composer\Package\Archiver\ArchiveManager;
use Composer\Util\Loop;

/**
 * Composer Factory
 */
class Factory extends \Composer\Factory
{
    /**
     * Copied from \Composer\Factory::createArchiveManager(), but without adding the zip/phar archivers
     * to avoid unnecessary server requirements.
     */
    public function createArchiveManager(Config $config, DownloadManager $dm, Loop $loop): ArchiveManager
    {
        return new ArchiveManager($dm, $loop);
    }
}
