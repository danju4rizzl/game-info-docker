<?php

if (!defined('ABSPATH')) {
    exit;
}

class PandaScore_Sync {
    private $settings;
    private $database;

    public function __construct($settings, $database) {
        $this->settings = $settings;
        $this->database = $database;
    }

    /**
     * Initialize hooks for WP-Cron scheduling.
     */
    public function init_hooks() {
        add_action('pandascore_sync_data', [$this, 'sync_all_data']);
        
        if (!wp_next_scheduled('pandascore_sync_data')) {
            wp_schedule_event(time(), 'pandascore_5min', 'pandascore_sync_data');
            error_log('[PandaScore Sync] Scheduled pandascore_sync_data event');
        }
    }

    public function sync_all_data() {
        error_log('[PandaScore Sync] Starting sync at ' . current_time('mysql'));
        
        $this->sync_tournaments(true);
        $this->sync_tournaments(false);
        $this->sync_matches_from_tournaments();
        $this->database->cleanup_old_matches(7);
        
        error_log('[PandaScore Sync] Completed sync at ' . current_time('mysql'));
    }

    private function sync_tournaments($is_live) {
        $api_key = $this->settings->get_api_key();
        if (!$api_key) {
            error_log('[PandaScore Sync] No API key configured');
            return;
        }

        $endpoint = $is_live ? '/tournaments/running' : '/tournaments/upcoming';
        $endpoint .= '?sort=&page[size]=100';
        $url = "https://api.pandascore.co/lol" . $endpoint;

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . $api_key]
        ]);

        if (is_wp_error($response)) {
            error_log('[PandaScore Sync] API Error: ' . $response->get_error_message());
            return;
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            error_log('[PandaScore Sync] API returned code ' . wp_remote_retrieve_response_code($response));
            return;
        }

        $tournaments = json_decode(wp_remote_retrieve_body($response), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[PandaScore Sync] Invalid JSON from API');
            return;
        }
        foreach ($tournaments as $tournament) {
            $tournament['is_live'] = $is_live;
            $this->database->save_tournament($tournament);
        }
        
        error_log("[PandaScore Sync] Saved " . count($tournaments) . " " . ($is_live ? 'live' : 'upcoming') . " tournaments");
    }

    private function sync_matches_from_tournaments() {
        $match_data = $this->database->get_tournament_match_ids();
        error_log("[PandaScore Sync] Found " . count($match_data) . " match IDs from tournaments");
        
        if (empty($match_data)) {
            error_log("[PandaScore Sync] No match IDs to sync");
            return;
        }
        
        $match_ids = array_column($match_data, 'match_id');
        $match_map = [];
        foreach ($match_data as $data) {
            $match_map[$data['match_id']] = [
                'tournament_id' => $data['tournament_id'],
                'league' => $data['league']
            ];
        }
        
        $chunks = array_chunk($match_ids, 50);
        $synced = 0;
        
        foreach ($chunks as $chunk) {
            $matches = $this->fetch_matches_batch($chunk);
            if ($matches) {
                foreach ($matches as $match) {
                    $match_id = $match['id'];
                    if (isset($match_map[$match_id])) {
                        $match['league'] = $match_map[$match_id]['league'];
                        $this->database->save_match($match, $match_map[$match_id]['tournament_id']);
                        $synced++;
                    }
                }
            }
        }
        
        error_log("[PandaScore Sync] Synced {$synced} matches from tournaments");
    }
    
    private function fetch_matches_batch($match_ids) {
        if (empty($match_ids)) return [];
        
        $api_key = $this->settings->get_api_key();
        $ids_string = implode(',', $match_ids);
        $url = "https://api.pandascore.co/lol/matches?filter[id]={$ids_string}&page[size]=100";
        
        error_log("[PandaScore Sync] Fetching batch of " . count($match_ids) . " matches");
        
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => ['Authorization' => 'Bearer ' . $api_key]
        ]);
        
        if (is_wp_error($response)) {
            error_log("[PandaScore Sync] Batch fetch error: " . $response->get_error_message());
            return [];
        }
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            error_log("[PandaScore Sync] Batch fetch returned code " . wp_remote_retrieve_response_code($response));
            return [];
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return (json_last_error() === JSON_ERROR_NONE) ? $data : [];
    }

    public function manual_sync() {
        $this->sync_all_data();
        return true;
    }

    public static function clear_scheduled_sync() {
        $timestamp = wp_next_scheduled('pandascore_sync_data');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'pandascore_sync_data');
            error_log('[PandaScore Sync] Cleared scheduled sync event');
        }
    }
}