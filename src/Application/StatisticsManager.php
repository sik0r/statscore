<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Event;
use App\Domain\EventType;
use App\Domain\StatisticRepositoryInterface;

final readonly class StatisticsManager
{
    public function __construct(private StatisticRepositoryInterface $statisticRepository) {}

    public function updateTeamStatistics(Event $event, int $value = 1): void
    {
        if (!$this->isSupported($event)) {
            return;
        }

        $matchId = $event->matchId();
        $teamId = $event->teamId();
        $statType = $event->isType(EventType::Foul) ? 'fouls' : 'goals';

        if (null === $matchId || null === $teamId) {
            throw new \InvalidArgumentException(sprintf('match_id and team_id are required for %s events', $event->type()->value));
        }

        $stats = $this->statisticRepository->getStatistics();
        $stats[$matchId][$teamId] ??= [];
        $stats[$matchId][$teamId][$statType] ??= 0;
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

    private function isSupported(Event $event): bool
    {
        return $event->isType(EventType::Foul) || $event->isType(EventType::Goal);
    }
}
