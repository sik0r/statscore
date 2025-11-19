<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Event;
use App\Domain\EventStorageInterface;

final readonly class FileEventStorage implements EventStorageInterface
{
    public function __construct(private string $filePath)
    {
        $directory = dirname($this->filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    public function save(Event $event): void
    {
        $line = json_encode($event).PHP_EOL;
        file_put_contents($this->filePath, $line, FILE_APPEND | LOCK_EX);
    }

    public function getAll(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);
        $lines = explode(PHP_EOL, trim($content));

        return array_map(function ($line) {
            return json_decode($line, true);
        }, array_filter($lines));
    }
}
