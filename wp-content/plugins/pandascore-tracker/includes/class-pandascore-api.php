<?php

if (!defined('ABSPATH')) {
    return;
}

class PandaScore_API {
    private $settings;
    private $cache;

    public function __construct($settings) {
        $this->settings = $settings;
        require_once plugin_dir_path(__FILE__) . 'class-pandascore-cache.php';
        $this->cache = new PandaScore_Cache($settings);
    }

    public function make_api_call($endpoint) {
        $api_key = $this->settings->get_api_key();
        if (!$api_key) return new WP_Error('no_api_key', 'PandaScore API key not set');

        $cache_key = "api_call_" . md5($endpoint);
        $cached_data = $this->cache->get($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }

        $url = "https://api.pandascore.co/lol{$endpoint}";
        
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . $api_key]
        ]);

        if (is_wp_error($response)) return $response;
        if (wp_remote_retrieve_response_code($response) !== 200) {
            return new WP_Error('api_error', 'PandaScore API returned code ' . wp_remote_retrieve_response_code($response));
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (json_last_error() !== JSON_ERROR_NONE) return new WP_Error('json_error', 'Invalid JSON from API');
        
        $this->cache->set($cache_key, $data);
        return $data;
    }

    public function get_tournament_matches($is_live = false, $league_type = 'top') {
        if ($league_type === 'top') {
            $tournaments = $this->get_top_league_tournaments($is_live);
        } elseif ($league_type === 'other') {
            $tournaments = $this->get_other_league_tournaments($is_live);
        } else {
            $tournaments = $this->get_tournaments_for_leagues($is_live);
        }
        
        if (is_wp_error($tournaments) || empty($tournaments)) {
            return [];
        }

        $all_matches = [];
        foreach ($tournaments as $tournament) {
            if (isset($tournament['matches']) && is_array($tournament['matches'])) {
                foreach ($tournament['matches'] as $match) {
                    $match['league'] = $tournament['league'];
                    $all_matches[] = $match;
                }
            }
        }
        return $all_matches;
    }

    public function get_top_league_matches($is_live = false) {
        return $this->get_tournament_matches($is_live, 'top');
    }

    public function get_other_league_matches($is_live = false) {
        return $this->get_tournament_matches($is_live, 'other');
    }

    public function get_all_tournament_matches($is_live = false) {
        return $this->get_tournament_matches($is_live, 'all');
    }

    public function get_tournaments_for_leagues($is_live = false) {
        $endpoint = $is_live ? '/tournaments/running' : '/tournaments/upcoming';
        $endpoint .= '?sort=&page[size]=100';
        $tournaments = $this->make_api_call($endpoint);
        
        if (is_wp_error($tournaments)) return $tournaments;
        
        // Fetch full match details for each tournament
        foreach ($tournaments as &$tournament) {
            if (isset($tournament['matches']) && is_array($tournament['matches'])) {
                $enriched_matches = [];
                foreach ($tournament['matches'] as $match) {
                    $match_id = $match['id'] ?? null;
                    if ($match_id) {
                        $full_match = $this->make_api_call("/matches/{$match_id}");
                        if (!is_wp_error($full_match)) {
                            $enriched_matches[] = $full_match;
                        }
                    }
                }
                $tournament['matches'] = $enriched_matches;
            }
        }
        
        return $tournaments;
    }

    public function get_top_league_tournaments($is_live = false) {
        $top_leagues = [
            'league-of-legends-world-championship',
            'league-of-legends-lck-champions-korea',
            'league-of-legends-lpl-china',
            'league-of-legends-lec',
            'league-of-legends-lta-south',
            'league-of-legends-lta-north',
        
          
        ];

        $all_tournaments = $this->get_tournaments_for_leagues($is_live);
        if (is_wp_error($all_tournaments)) return $all_tournaments;
        
        $filtered_tournaments = [];
        foreach ($all_tournaments as $tournament) {
            $league_slug = $tournament['league']['slug'] ?? '';
            if (in_array($league_slug, $top_leagues)) {
                $filtered_tournaments[] = $tournament;
            }
        }
        
        return $filtered_tournaments;
    }

    public function get_other_league_tournaments($is_live = false) {
         $top_leagues = [
            'league-of-legends-world-championship',
            'league-of-legends-lck-champions-korea',
            'league-of-legends-lpl-china',
            'league-of-legends-lec',
            'league-of-legends-lta-south',
            'league-of-legends-lta-north',
        ];

        $all_tournaments = $this->get_tournaments_for_leagues($is_live);
        if (is_wp_error($all_tournaments)) return $all_tournaments;
        
        $other_tournaments = [];
        foreach ($all_tournaments as $tournament) {
            $league_slug = $tournament['league']['slug'] ?? '';
            if (!in_array($league_slug, $top_leagues)) {
                $other_tournaments[] = $tournament;
            }
        }
        
        return $other_tournaments;
    }

    public function get_live_matches_from_tournaments($game) {
        $tournaments = $this->get_top_league_tournaments(true);
        if (is_wp_error($tournaments) || empty($tournaments)) {
            return [];
        }

        $live_matches = [];
        foreach ($tournaments as $tournament) {
            if (!isset($tournament['matches']) || !is_array($tournament['matches'])) continue;

            foreach ($tournament['matches'] as $match) {
                if (isset($match['live']['supported']) && $match['live']['supported'] === true) {
                    $match_data = [
                        'match_id' => $match['id'],
                        'status' => $match['status'] ?? 'unknown',
                        'live_url' => $match['live']['url'] ?? null,
                        'opens_at' => $match['live']['opens_at'] ?? null
                    ];

                    if (in_array($match_data['status'], ['running', 'not_started'])) {
                        $live_matches[] = $match_data;
                    }
                }
            }
        }

        return $live_matches;
    }

    public function get_ws_matches_payload($matchIds = []) {
        $api_key = $this->settings->get_api_key();
        $matchIds = array_values(array_unique(array_map('intval', (array) $matchIds)));
        
        if (empty($matchIds) || !$api_key) return [];

        $payload = [];
        foreach ($matchIds as $id) {
            $payload[] = ['match_id' => $id];
        }

        return $payload;
    }
}