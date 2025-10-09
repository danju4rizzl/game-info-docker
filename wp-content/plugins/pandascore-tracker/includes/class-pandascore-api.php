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

    public function get_live_matches_from_tournaments($game) {
        $api_key = $this->settings->get_api_key();
        if (!$api_key) return [];

        $cache_key = "live_tournaments_{$game}";
        $cached_data = $this->cache->get($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }

        $live_matches = [];
        $tournaments_url = "https://api.pandascore.co/{$game}/tournaments/running";
        $response = wp_remote_get($tournaments_url, [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . $api_key]
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $error_msg = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_response_code($response);
            error_log('[PandaScore] Failed to fetch tournaments: ' . sanitize_text_field($error_msg));
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
                        error_log('[PandaScore] Found live-supported match: ' . intval($match_data['match_id']) . ' (status: ' . sanitize_text_field($match_data['status']) . ')');
                    }
                }
            }
        }

        $this->cache->set($cache_key, $live_matches, 2 * MINUTE_IN_SECONDS);
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