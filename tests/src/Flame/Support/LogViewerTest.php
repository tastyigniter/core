<?php

namespace Igniter\Tests\Flame\Support;

use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\LogViewer;
use InvalidArgumentException;

beforeEach(function() {
    $this->logViewer = resolve(LogViewer::class);
});

it('sets the log file path correctly', function() {
    $filePath = storage_path('logs/example.log');
    File::shouldReceive('basename')->with($filePath)->andReturn('example.log');

    expect($this->logViewer->setFile($filePath)->getFileName())->toBe('example.log');
});

it('throws exception for invalid log file path', function() {
    $filePath = '../example.log';
    expect(fn() => $this->logViewer->setFile($filePath))->toThrow(InvalidArgumentException::class, 'Invalid log file');
});

it('returns all log entries from first log file', function() {
    $logContent = "[2023-10-01 12:00:00] local.INFO: This is an info log\n[2023-10-01 12:01:00] local.ERROR: This is an error log";
    $filePath = storage_path('logs/example.log');
    File::shouldReceive('glob')->with(storage_path('logs/*.log'))->andReturn([], [$filePath]);
    File::shouldReceive('isFile')->andReturnTrue();
    File::shouldReceive('basename')->with($filePath)->andReturn('example.log');
    File::shouldReceive('size')->with($filePath)->andReturn(1024);
    File::shouldReceive('get')->with($filePath)->andReturn($logContent);

    expect($this->logViewer->all())->toBe([]);

    $result = $this->logViewer->all();

    expect($result)->toHaveCount(2)
        ->and($result[0]['level'])->toBe('ERROR')
        ->and($result[1]['level'])->toBe('INFO');
});

it('returns all log entries', function() {
    $logContent = "[2023-10-01 12:00:00] local.INFO: This is an info log\n[2023-10-01 12:01:00] local.ERROR: This is an error log";
    $filePath = storage_path('logs/example.log');
    File::shouldReceive('size')->with($filePath)->andReturn(1024);
    File::shouldReceive('get')->with($filePath)->andReturn($logContent);

    $result = $this->logViewer->setFile($filePath)->all();

    expect($result)->toHaveCount(2)
        ->and($result[0]['level'])->toBe('ERROR')
        ->and($result[1]['level'])->toBe('INFO');
});

it('skips log entries with missing log headings', function() {
    $logContent = "This is an info log";
    $filePath = storage_path('logs/example.log');
    File::shouldReceive('size')->with($filePath)->andReturn(1024);
    File::shouldReceive('get')->with($filePath)->andReturn($logContent);

    expect($this->logViewer->setFile($filePath)->all())->toBe([]);
});

it('skips log entries with missing log level', function() {
    $logContent = "[2023-10-01 12:00:00] local.INFO:";
    $filePath = storage_path('logs/example.log');
    File::shouldReceive('size')->with($filePath)->andReturn(1024);
    File::shouldReceive('get')->with($filePath)->andReturn($logContent);

    expect($this->logViewer->setFile($filePath)->all())->toBe([]);
});

it('returns null for large log file', function() {
    $filePath = storage_path('logs/large.log');
    File::shouldReceive('size')->with($filePath)->andReturn(LogViewer::MAX_FILE_SIZE + 1);

    expect($this->logViewer->setFile($filePath)->all())->toBeNull();
});

it('returns log files with basename', function() {
    $logFiles = [
        $logFile1 = storage_path('logs/2023-10-01.log'),
        $logFile2 = storage_path('logs/2023-10-02.log'),
    ];
    File::shouldReceive('glob')->with(storage_path('logs/*.log'))->andReturn($logFiles);
    File::shouldReceive('isFile')->andReturnTrue();
    File::shouldReceive('basename')->with($logFile1)->andReturn('2023-10-01.log');
    File::shouldReceive('basename')->with($logFile2)->andReturn('2023-10-02.log');

    $result = $this->logViewer->getFiles(true);

    expect($result)->toContain('2023-10-01.log')
        ->and($result)->toContain('2023-10-02.log');
});
