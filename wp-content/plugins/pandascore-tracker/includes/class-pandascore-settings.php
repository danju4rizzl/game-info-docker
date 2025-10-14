<?php

if (!defined('ABSPATH')) {
    exit;
}

class PandaScore_Settings {
    private $option_key = 'pandascore_tracker_options';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'handle_cache_clear']);
    }

    public function handle_cache_clear() {
        if (isset($_POST['action']) && $_POST['action'] === 'clear_cache' && 
            wp_verify_nonce($_POST['pandascore_cache_nonce'], 'pandascore_clear_cache')) {
            require_once plugin_dir_path(__FILE__) . 'class-pandascore-cache.php';
            $cache = new PandaScore_Cache($this);
            $cache->clear_all();
        }
    }

    public function admin_menu() {
        add_options_page('PandaScore Tracker', 'PandaScore Tracker', 'manage_options', 'pandascore-tracker', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting($this->option_key, $this->option_key);
        add_settings_section('pandascore_main', 'PandaScore Settings', null, 'pandascore-tracker');
        add_settings_field('api_key', 'API Key', [$this, 'field_api_key'], 'pandascore-tracker', 'pandascore_main');
        add_settings_field('cache_duration', 'Cache Duration (minutes)', [$this, 'field_cache_duration'], 'pandascore-tracker', 'pandascore_main');
    }

    public function field_api_key() {
        $opts = get_option($this->option_key);
        $val = isset($opts['api_key']) ? esc_attr($opts['api_key']) : '';
        echo '<input type="text" name="' . $this->option_key . '[api_key]" value="' . $val . '" class="pandascore-api-key-input">';
    }

    public function field_cache_duration() {
        $opts = get_option($this->option_key);
        $val = isset($opts['cache_duration']) ? intval($opts['cache_duration']) : 10;
        echo '<input type="number" min="1" max="60" name="' . $this->option_key . '[cache_duration]" value="' . $val . '" /> minutes';
        echo '<p class="description">Cache API responses to reduce rate limit usage (1-60 minutes)</p>';
    }

    public function settings_page() {
        wp_enqueue_style('pandascore-tracker-style');
        
        if (isset($_POST['action']) && $_POST['action'] === 'manual_sync' && 
            isset($_POST['pandascore_sync_nonce']) &&
            wp_verify_nonce($_POST['pandascore_sync_nonce'], 'pandascore_manual_sync')) {
            require_once plugin_dir_path(__FILE__) . 'class-pandascore-database.php';
            require_once plugin_dir_path(__FILE__) . 'class-pandascore-sync.php';
            $database = new PandaScore_Database();
            $sync = new PandaScore_Sync($this, $database);
            $sync->manual_sync();
            echo '<div class="notice notice-success is-dismissible"><p>Manual sync completed!</p></div>';
        }
        
        require_once plugin_dir_path(__FILE__) . 'class-pandascore-database.php';
        $database = new PandaScore_Database();
        $debug_info = $database->get_debug_info();
        $last_sync = $database->get_last_sync_time();
        $last_sync_text = $last_sync ? human_time_diff($last_sync, current_time('timestamp')) . ' ago' : 'Never';
        
        echo '<script>console.log("[PandaScore Debug]", ' . json_encode($debug_info) . ');</script>';
        
        ?>
        <div class="wrap">
            <h1>PandaScore Tracker</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_key);
                do_settings_sections('pandascore-tracker');
                submit_button();
                ?>
            </form>
            <h3>Data Management</h3>
            <p><strong>Last Sync:</strong> <?php echo esc_html($last_sync_text); ?></p>
            <p><strong>Tournaments:</strong> <?php echo esc_html($debug_info['tournaments_count']); ?></p>
            <p><strong>Matches:</strong> <?php echo esc_html($debug_info['matches_count']); ?></p>
            <form method="post" action="" style="display:inline-block;margin-right:10px;">
                <?php wp_nonce_field('pandascore_manual_sync', 'pandascore_sync_nonce'); ?>
                <input type="hidden" name="action" value="manual_sync" />
                <input type="submit" class="button button-primary" value="Sync Now" />
            </form>
            <form method="post" action="" style="display:inline-block;">
                <?php wp_nonce_field('pandascore_clear_cache', 'pandascore_cache_nonce'); ?>
                <input type="hidden" name="action" value="clear_cache" />
                <input type="submit" class="button" value="Clear Cache" />
            </form>
            <h3>Shortcode Usage</h3>
            <p><strong>Basic usage:</strong> <code>[pandascore_tracker]</code></p>
            <p><strong>Live matches:</strong> <code>[pandascore_tracker type="live"]</code></p>
            <p><strong>Mixed (live + upcoming):</strong> <code>[pandascore_tracker type="mixed" game="lol"]</code></p>
            <h4>Parameters:</h4>
            <ul>
                <li><strong>game:</strong> Game type (valorant, lol, csgo, dota2, etc.)</li>
                <li><strong>type:</strong> Match type - "upcoming" (default), "live", or "mixed"</li>
            </ul>
        </div>
        <?php
    }

    public function get_api_key() {
        $opts = get_option($this->option_key);
        return isset($opts['api_key']) ? trim($opts['api_key']) : '';
    }

    public function get_cache_duration() {
        $opts = get_option($this->option_key);
        $minutes = isset($opts['cache_duration']) ? intval($opts['cache_duration']) : 10;
        return $minutes * MINUTE_IN_SECONDS;
    }
}