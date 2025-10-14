<?php

if (!defined('ABSPATH')) {
    exit;
}

class PandaScore_Database {
    private $tournaments_table;
    private $matches_table;

    public function __construct() {
        global $wpdb;
        $this->tournaments_table = $wpdb->prefix . 'pandascore_tournaments';
        $this->matches_table = $wpdb->prefix . 'pandascore_matches';
    }

    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_tournaments = "CREATE TABLE {$this->tournaments_table} (
            id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            league_id bigint(20) NOT NULL,
            league_name varchar(255) NOT NULL,
            league_slug varchar(255) NOT NULL,
            league_image_url text,
            begin_at datetime DEFAULT NULL,
            end_at datetime DEFAULT NULL,
            is_live tinyint(1) DEFAULT 0,
            raw_data longtext NOT NULL,
            last_synced datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY league_slug (league_slug),
            KEY is_live (is_live),
            KEY last_synced (last_synced)
        ) $charset_collate;";

        $sql_matches = "CREATE TABLE {$this->matches_table} (
            id bigint(20) NOT NULL,
            tournament_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            status varchar(50) NOT NULL,
            scheduled_at datetime DEFAULT NULL,
            begin_at datetime DEFAULT NULL,
            end_at datetime DEFAULT NULL,
            league_id bigint(20) NOT NULL,
            league_name varchar(255) NOT NULL,
            league_slug varchar(255) NOT NULL,
            league_image_url text,
            raw_data longtext NOT NULL,
            last_synced datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY tournament_id (tournament_id),
            KEY status (status),
            KEY league_slug (league_slug),
            KEY scheduled_at (scheduled_at),
            KEY last_synced (last_synced)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_tournaments);
        dbDelta($sql_matches);
    }

    public function drop_tables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->matches_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->tournaments_table}");
    }

    public function save_tournament($tournament_data) {
        global $wpdb;

        $league = $tournament_data['league'] ?? [];
        
        $result = $wpdb->replace(
            $this->tournaments_table,
            [
                'id' => $tournament_data['id'],
                'name' => $tournament_data['name'] ?? '',
                'slug' => $tournament_data['slug'] ?? '',
                'league_id' => $league['id'] ?? 0,
                'league_name' => $league['name'] ?? '',
                'league_slug' => $league['slug'] ?? '',
                'league_image_url' => $league['image_url'] ?? '',
                'begin_at' => $tournament_data['begin_at'] ?? null,
                'end_at' => $tournament_data['end_at'] ?? null,
                'is_live' => isset($tournament_data['is_live']) ? (int)$tournament_data['is_live'] : 0,
                'raw_data' => json_encode($tournament_data),
                'last_synced' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        if ($result === false) {
            error_log("[DB Debug] Failed to save tournament {$tournament_data['id']}: " . $wpdb->last_error);
        }
        
        return $result;
    }

    public function save_match($match_data, $tournament_id = null) {
        global $wpdb;

        $league = $match_data['league'] ?? [];
        
        $result = $wpdb->replace(
            $this->matches_table,
            [
                'id' => $match_data['id'],
                'tournament_id' => $tournament_id ?? ($match_data['tournament_id'] ?? 0),
                'name' => $match_data['name'] ?? '',
                'status' => $match_data['status'] ?? 'unknown',
                'scheduled_at' => $match_data['scheduled_at'] ?? null,
                'begin_at' => $match_data['begin_at'] ?? null,
                'end_at' => $match_data['end_at'] ?? null,
                'league_id' => $league['id'] ?? 0,
                'league_name' => $league['name'] ?? '',
                'league_slug' => $league['slug'] ?? '',
                'league_image_url' => $league['image_url'] ?? '',
                'raw_data' => json_encode($match_data),
                'last_synced' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            error_log("[DB Debug] Failed to save match {$match_data['id']}: " . $wpdb->last_error);
        }
        
        return $result;
    }

    public function get_matches($is_live = false, $league_type = 'all') {
        global $wpdb;

        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->matches_table}");
        error_log("[DB Debug] Total matches in database: {$total_count}");
        
        $all_statuses = $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$this->matches_table} GROUP BY status");
        error_log("[DB Debug] Match statuses: " . print_r($all_statuses, true));

        $status_condition = $is_live ? "status = 'running'" : "status = 'not_started'";
        
        $league_condition = '';
        if ($league_type === 'top') {
            $top_leagues = [
                'league-of-legends-world-championship',
                'league-of-legends-lck-champions-korea',
                'league-of-legends-lpl-china',
                'league-of-legends-lec',
                'league-of-legends-lta-south',
                'league-of-legends-lta-north'
            ];
            $placeholders = implode(',', array_fill(0, count($top_leagues), '%s'));
            $league_condition = $wpdb->prepare(" AND league_slug IN ($placeholders)", $top_leagues);
        } elseif ($league_type === 'other') {
            $top_leagues = [
                'league-of-legends-world-championship',
                'league-of-legends-lck-champions-korea',
                'league-of-legends-lpl-china',
                'league-of-legends-lec',
                'league-of-legends-lta-south',
                'league-of-legends-lta-north'
            ];
            $placeholders = implode(',', array_fill(0, count($top_leagues), '%s'));
            $league_condition = $wpdb->prepare(" AND league_slug NOT IN ($placeholders)", $top_leagues);
        }

        $query = "SELECT raw_data FROM {$this->matches_table} 
                  WHERE {$status_condition} {$league_condition}
                  ORDER BY scheduled_at ASC";

        error_log("[DB Debug] Query: {$query}");
        $results = $wpdb->get_results($query);
        error_log("[DB Debug] Results count: " . count($results));
        
        $matches = [];
        foreach ($results as $row) {
            $matches[] = json_decode($row->raw_data, true);
        }
        
        return $matches;
    }

    public function get_match_by_id($match_id) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT raw_data FROM {$this->matches_table} WHERE id = %d",
            $match_id
        ));
        
        return $result ? json_decode($result, true) : null;
    }

    public function get_tournament_by_id($tournament_id) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT raw_data FROM {$this->tournaments_table} WHERE id = %d",
            $tournament_id
        ));
        
        return $result ? json_decode($result, true) : null;
    }

    public function get_live_match_ids() {
        global $wpdb;
        
        return $wpdb->get_col(
            "SELECT id FROM {$this->matches_table} 
             WHERE status IN ('running', 'not_started')"
        );
    }

    public function cleanup_old_matches($days = 7) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->matches_table} 
             WHERE last_synced < DATE_SUB(NOW(), INTERVAL %d DAY)
             AND status NOT IN ('running', 'not_started')",
            $days
        ));
    }

    public function get_last_sync_time() {
        global $wpdb;
        
        $result = $wpdb->get_var(
            "SELECT MAX(last_synced) FROM {$this->matches_table}"
        );
        
        error_log("[DB Debug] Last sync raw value: " . var_export($result, true));
        
        return $result ? strtotime($result) : 0;
    }
    
    public function get_debug_info() {
        global $wpdb;
        
        return [
            'tournaments_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->tournaments_table}"),
            'matches_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->matches_table}"),
            'match_statuses' => $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$this->matches_table} GROUP BY status", ARRAY_A),
            'last_sync' => $wpdb->get_var("SELECT MAX(last_synced) FROM {$this->matches_table}")
        ];
    }
    
    public function get_tournament_match_ids() {
        global $wpdb;
        
        $results = $wpdb->get_results(
            "SELECT id, raw_data FROM {$this->tournaments_table}",
            ARRAY_A
        );
        
        $match_data = [];
        foreach ($results as $row) {
            $tournament = json_decode($row['raw_data'], true);
            if (isset($tournament['matches']) && is_array($tournament['matches'])) {
                foreach ($tournament['matches'] as $match) {
                    if (isset($match['id'])) {
                        $match_data[] = [
                            'match_id' => $match['id'],
                            'tournament_id' => $tournament['id'],
                            'league' => $tournament['league'] ?? []
                        ];
                    }
                }
            }
        }
        
        return $match_data;
    }
}
