<?php

if (!defined('ABSPATH')) {
    exit;
}

class PandaScore_Assets {
    private $plugin_file;

    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    public function register_assets() {
        wp_register_style('pandascore-tracker-style', plugins_url('css/index.css', $this->plugin_file), [], '1.3');
        wp_register_script('pandascore-live-tracker-js', plugins_url('js/live-tracker.js', $this->plugin_file), [], '1.3', true);
        wp_register_script('pandascore-timezone-js', plugins_url('js/timezone-converter.js', $this->plugin_file), [], '1.0', true);
        wp_register_script('pandascore-league-filter-js', plugins_url('js/league-filter.js', $this->plugin_file), [], '1.0', true);
        wp_register_script('pandascore-date-filter-js', plugins_url('js/date-filter.js', $this->plugin_file), [], '1.0', true);
    }

    public function enqueue_basic_assets() {
        wp_enqueue_style('pandascore-tracker-style');
        wp_enqueue_script('pandascore-timezone-js');
        wp_enqueue_script('pandascore-league-filter-js');
        wp_enqueue_script('pandascore-date-filter-js');
    }

    public function enqueue_live_tracker($api_key, $ws_matches) {
        wp_enqueue_script('pandascore-live-tracker-js');
        wp_localize_script('pandascore-live-tracker-js', 'pandaScoreLiveTracker', [
            'apiKey' => $api_key,
            'wsMatches' => $ws_matches,
        ]);
    }
}