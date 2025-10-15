<?php

if (!defined('ABSPATH')) {
    exit;
}

class PandaScore_Match_Details {
    private $database;
    private $match;

    public function __construct($match_id) {
        $this->database = new PandaScore_Database();
        $this->match = $this->database->get_match_by_id($match_id);
    }

    public function get_match() {
        return $this->match;
    }

    public function get_league_info() {
        return [
            'name' => $this->match['league']['name'] ?? 'Unknown League',
            'tournament_name' => $this->match['tournament']['name'] ?? '',
            'match_name' => $this->match['name'] ?? '',
            'scheduled_at' => $this->match['scheduled_at'] ?? ''
        ];
    }

    public function get_teams() {
        $opponents = $this->match['opponents'] ?? [];
        $results = $this->match['results'] ?? [];
        
        return [
            'teamA' => $opponents[0]['opponent'] ?? ['name' => 'T1', 'acronym' => 'T1'],
            'teamB' => $opponents[1]['opponent'] ?? ['name' => 'DRX', 'acronym' => 'DRX'],
            'scoreA' => $results[0]['score'] ?? 0,
            'scoreB' => $results[1]['score'] ?? 0
        ];
    }

    public function get_sorted_players() {
        $players = [];
        
        if (!isset($this->match['tournament_id'])) {
            return ['team1' => [], 'team2' => []];
        }

        $tournament = $this->database->get_tournament_by_id($this->match['tournament_id']);
        if (!$tournament || !isset($tournament['expected_roster'])) {
            return ['team1' => [], 'team2' => []];
        }

        $teams = $this->get_teams();
        $teamA_id = $teams['teamA']['id'] ?? null;
        $teamB_id = $teams['teamB']['id'] ?? null;

        foreach ($tournament['expected_roster'] as $roster) {
            if (isset($roster['team']['id']) && isset($roster['players'])) {
                if ($roster['team']['id'] == $teamA_id) {
                    $players = array_merge($players, $roster['players']);
                } elseif ($roster['team']['id'] == $teamB_id) {
                    $players = array_merge($players, $roster['players']);
                }
            }
        }

        $this->sort_players_by_role($players);

        return [
            'team1' => array_slice($players, 0, 5),
            'team2' => array_slice($players, 5, 5)
        ];
    }

    public function get_main_stream() {
        $streams = $this->match['streams_list'] ?? [];
        
        foreach ($streams as $stream) {
            if ($stream['main'] === true) {
                return $stream['embed_url'];
            }
        }
        
        return null;
    }

    private function sort_players_by_role(&$players) {
        $role_order = ['top' => 0, 'jun' => 1, 'mid' => 2, 'adc' => 3, 'sup' => 4];
        
        usort($players, function($a, $b) use ($role_order) {
            $role_a = strtolower($a['role'] ?? '');
            $role_b = strtolower($b['role'] ?? '');
            return ($role_order[$role_a] ?? 99) - ($role_order[$role_b] ?? 99);
        });
    }
}
