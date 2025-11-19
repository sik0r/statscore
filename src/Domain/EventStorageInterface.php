<?php

declare(strict_types=1);

namespace App\Domain;

interface EventStorageInterface
{
    public function save(Event $event): void;
}
