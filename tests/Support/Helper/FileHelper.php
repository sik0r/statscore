<?php

namespace Tests\Support\Helper;

class FileHelper extends \Codeception\Module
{
    public function deleteFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
