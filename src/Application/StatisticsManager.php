<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\StatisticRepositoryInterface;

final readonly class StatisticsManager
{
    public function __construct(private StatisticRepositoryInterface $statisticRepository) {}

    public function updateTeamStatistics(string $matchId, string $teamId, string $statType, int $value = 1): void
    {
        $stats = $this->statisticRepository->getStatistics();

        if (!isset($stats[$matchId][$teamId])) {
            $stats[$matchId][$teamId] = [];
        }

        if (!isset($stats[$matchId][$teamId][$statType])) {
            $stats[$matchId][$teamId][$statType] = 0;
        }

        $stats[$matchId][$teamId][$statType] += $value;

        $this->statisticRepository->saveStatistics($stats);
    }

    public function getTeamStatistics(string $matchId, string $teamId): array
    {
        $stats = $this->statisticRepository->getStatistics();

        return $stats[$matchId][$teamId] ?? [];
    }

    public function getMatchStatistics(string $matchId): array
    {
        $stats = $this->statisticRepository->getStatistics();

        return $stats[$matchId] ?? [];
    }
}
