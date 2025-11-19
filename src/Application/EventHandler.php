<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Event;
use App\Domain\EventStorageInterface;
use App\Domain\EventType;

final readonly class EventHandler
{
    private StatisticsManager $statisticsManager;

    public function __construct(private EventStorageInterface $eventStorage, ?StatisticsManager $statisticsManager = null)
    {
        $this->statisticsManager = $statisticsManager ?? new StatisticsManager(__DIR__.'/../../storage/statistics.txt');
    }

    /**
     * @return array{status: string, message: string, event: Event} $data
     */
    public function handleEvent(array $data): array
    {
        if (!isset($data['type'])) {
            throw new \InvalidArgumentException('Event type is required');
        }

        $event = new Event(EventType::from($data['type']), time(), $data);
        $this->eventStorage->save($event);

        // todo: dispatch event `event.event.created` and decouple StatisticsManager from EventHandler

        // Update statistics for foul events
        if ($event->isType(EventType::Foul)) {
            if (!isset($data['match_id']) || !isset($data['team_id'])) {
                throw new \InvalidArgumentException('match_id and team_id are required for foul events');
            }

            $this->statisticsManager->updateTeamStatistics(
                $data['match_id'],
                $data['team_id'],
                'fouls'
            );
        }

        return [
            'status' => 'success',
            'message' => 'Event saved successfully',
            'event' => $event,
        ];
    }
}
