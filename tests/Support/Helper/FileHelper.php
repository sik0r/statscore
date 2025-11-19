<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Module;

class FileHelper extends Module
{
    public function deleteFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
