<?php

declare(strict_types=1);

namespace App\Domain;

interface StatisticRepositoryInterface
{
    public function saveStatistics(array $stats): void;

    public function getStatistics(): array;
}
