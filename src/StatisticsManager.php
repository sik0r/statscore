<?php

namespace App;

class StatisticsManager
{
    private FileStorage $storage;
    private string $statsFile;
    
    public function __construct(string $statsFile = '../storage/statistics.txt')
    {
        $this->storage = new FileStorage($statsFile);
        $this->statsFile = $statsFile;
        
        $directory = dirname($statsFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }
    
    public function updateTeamStatistics(string $matchId, string $teamId, string $statType, int $value = 1): void
    {
        $stats = $this->getStatistics();
        
        if (!isset($stats[$matchId])) {
            $stats[$matchId] = [];
        }
        
        if (!isset($stats[$matchId][$teamId])) {
            $stats[$matchId][$teamId] = [];
        }
        
        if (!isset($stats[$matchId][$teamId][$statType])) {
            $stats[$matchId][$teamId][$statType] = 0;
        }
        
        $stats[$matchId][$teamId][$statType] += $value;
        
        $this->saveStatistics($stats);
    }
    
    public function getTeamStatistics(string $matchId, string $teamId): array
    {
        $stats = $this->getStatistics();
        return $stats[$matchId][$teamId] ?? [];
    }
    
    public function getMatchStatistics(string $matchId): array
    {
        $stats = $this->getStatistics();
        return $stats[$matchId] ?? [];
    }
    
    private function getStatistics(): array
    {
        if (!file_exists($this->statsFile)) {
            return [];
        }
        
        $content = file_get_contents($this->statsFile);
        return json_decode($content, true) ?? [];
    }
    
    private function saveStatistics(array $stats): void
    {
        file_put_contents($this->statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);
    }
}
