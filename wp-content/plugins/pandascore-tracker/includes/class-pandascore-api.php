<?php

if (!defined('ABSPATH')) {
    exit;
}

class PandaScore_API {
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
    }

    public function make_api_call($game, $limit, $endpoint) {
        $api_key = $this->settings->get_api_key();
        if (!$api_key) return new WP_Error('no_api_key', 'PandaScore API key not set');

        $query_args = ['page[size]' => intval($limit)];
        $url = add_query_arg($query_args, "https://api.pandascore.co/{$game}/matches/{$endpoint}");
        
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
        return $data;
    }

    public function get_live_matches_from_tournaments($game) {
        $api_key = $this->settings->get_api_key();
        if (!$api_key) return [];

        $live_matches = [];
        $tournaments_url = "https://api.pandascore.co/{$game}/tournaments/running";
        $response = wp_remote_get($tournaments_url, [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . $api_key]
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            error_log('[PandaScore] Failed to fetch tournaments: ' . (is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_response_code($response)));
            return [];
        }

        $tournaments = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($tournaments)) return [];

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
                        error_log("[PandaScore] Found live-supported match: {$match_data['match_id']} (status: {$match_data['status']})");
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