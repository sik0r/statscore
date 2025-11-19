<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\StatisticRepositoryInterface;

final readonly class FileStatisticRepository implements StatisticRepositoryInterface
{
    public function __construct(private string $filePath)
    {
        $directory = dirname($this->filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    public function saveStatistics(array $stats): void
    {
        file_put_contents($this->filePath, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);
    }

    public function getStatistics(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);

        return json_decode($content, true) ?? [];
    }
}
