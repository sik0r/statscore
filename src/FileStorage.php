<?php

namespace App;

class FileStorage
{
    private string $filePath;
    
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }
    
    public function save(array $event): void
    {
        $line = json_encode($event) . PHP_EOL;
        file_put_contents($this->filePath, $line, FILE_APPEND | LOCK_EX);
    }
    
    public function getAll(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }
        
        $content = file_get_contents($this->filePath);
        $lines = explode(PHP_EOL, trim($content));
        
        return array_map(function($line) {
            return json_decode($line, true);
        }, array_filter($lines));
    }
}