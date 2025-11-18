<?php

namespace Tests\Api;

use Tests\Support\ApiTester;

class EventApiCest
{
    public function _before(ApiTester $I)
    {
        // Clean up storage files before each test
        $I->deleteFile('storage/events.txt');
        $I->deleteFile('storage/statistics.txt');
    }

    public function testFoulEvent(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'type' => 'foul',
            'player' => 'William Saliba',
            'team_id' => 'arsenal',
            'match_id' => 'm1',
            'minute' => 45,
            'second' => 34
        ]);
        
        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'Event saved successfully'
        ]);
        $I->seeResponseJsonMatchesJsonPath('$.event.type', 'foul');
    }

    public function testFoulEventWithoutRequiredFields(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'type' => 'foul',
            'player' => 'William Saliba',
                'minute' => 45,
            'second' => 34
            // Missing team_id and match_id
        ]);
        
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'error' => 'match_id and team_id are required for foul events'
        ]);
    }

    public function testInvalidJson(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', 'invalid json');
        
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'error' => 'Invalid JSON'
        ]);
    }

    public function testEventWithoutType(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'player' => 'John Doe',
            'minute' => 23,
            'second' => 34
        ]);
        
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'error' => 'Event type is required'
        ]);
    }
}
