<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use App\Application\EventHandler;
use App\Application\StatisticsManager;
use App\Infrastructure\FileEventStorage;
use App\Infrastructure\FileStatisticRepository;

header('Content-Type: application/json');

// Simple routing
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$statsManager = new StatisticsManager(new FileStatisticRepository(__DIR__.'/../storage/statistics.txt'));

if ('POST' === $method && '/event' === $path) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (JSON_ERROR_NONE !== json_last_error()) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);

        exit;
    }

    $eventStorage = new FileEventStorage(__DIR__.'/../storage/events.txt');
    $handler = new EventHandler(eventStorage: $eventStorage, statisticsManager: $statsManager);

    try {
        $result = $handler->handleEvent($data);
        http_response_code(201);
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ('GET' === $method && '/statistics' === $path) {
    $matchId = $_GET['match_id'] ?? null;
    $teamId = $_GET['team_id'] ?? null;

    try {
        if ($matchId && $teamId) {
            // Get team statistics for specific match
            $stats = $statsManager->getTeamStatistics($matchId, $teamId);
            echo json_encode([
                'match_id' => $matchId,
                'team_id' => $teamId,
                'statistics' => $stats,
            ]);
        } elseif ($matchId) {
            // Get all team statistics for specific match
            $stats = $statsManager->getMatchStatistics($matchId);
            echo json_encode([
                'match_id' => $matchId,
                'statistics' => $stats,
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'match_id is required']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
