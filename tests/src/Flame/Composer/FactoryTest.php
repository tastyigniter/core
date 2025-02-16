<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Composer;

use Composer\Config;
use Composer\Downloader\DownloadManager;
use Composer\Package\Archiver\ArchiveManager;
use Composer\Util\Loop;
use Igniter\Flame\Composer\Factory;

it('creates an archive manager without zip/phar archivers', function() {
    $config = mock(Config::class);
    $downloadManager = mock(DownloadManager::class);
    $loop = mock(Loop::class);

    $factory = new Factory;
    $archiveManager = $factory->createArchiveManager($config, $downloadManager, $loop);

    expect($archiveManager)->toBeInstanceOf(ArchiveManager::class);
});
