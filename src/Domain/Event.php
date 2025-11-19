<?php

declare(strict_types=1);

namespace App\Domain;

class Event implements \JsonSerializable
{
    private EventType $type;
    private int $timestamp;
    private array $data;

    public function __construct(EventType $type, int $timestamp, array $data)
    {
        $this->type = $type;
        $this->timestamp = $timestamp;
        $this->data = $data;
    }

    public function matchId(): ?string
    {
        return $this->data['match_id'] ?? null;
    }

    public function teamId(): ?string
    {
        return $this->data['team_id'] ?? null;
    }

    public function type(): EventType
    {
        return $this->type;
    }

    public function isType(EventType $type): bool
    {
        return $this->type === $type;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'type' => $this->type->value,
            'timestamp' => $this->timestamp,
            'data' => $this->data,
        ];
    }
}
