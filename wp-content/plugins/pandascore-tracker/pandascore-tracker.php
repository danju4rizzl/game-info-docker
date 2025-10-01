<?php
/*
Plugin Name: PandaScore Tracker
Description: Fetches and displays PandaScore game scores via shortcode.
Version: 1.3 (Improved WebSocket Implementation + Match Details Routing)
Author: Deejay Dev
Text Domain: pandascore-tracker
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-pandascore-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-pandascore-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-pandascore-renderer.php';

class PandaScore_Tracker_Plugin {
    private $live_match_ids = [];
    private $settings;
    private $api;
    private $renderer;

    public function __construct() {
        $this->settings = new PandaScore_Settings();
        $this->api = new PandaScore_API($this->settings);
        $this->renderer = new PandaScore_Renderer(__FILE__);
        add_shortcode('pandascore_tracker', [$this, 'shortcode_handler']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Routing for match details page
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_filter('template_include', [$this, 'match_template_include']);
    }

    public function enqueue_assets() {
        wp_register_style('pandascore-tracker-style', plugins_url('css/index.css', __FILE__), [], '1.3');
        wp_register_script('pandascore-live-tracker-js', plugins_url('js/live-tracker.js', __FILE__), [], '1.3', true);
        wp_register_script('pandascore-timezone-js', plugins_url('js/timezone-converter.js', __FILE__), [], '1.0', true);
        wp_register_script('pandascore-league-filter-js', plugins_url('js/league-filter.js', __FILE__), [], '1.0', true);
        wp_register_script('pandascore-date-filter-js', plugins_url('js/date-filter.js', __FILE__), [], '1.0', true);
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^match/([0-9]+)/?$', 'index.php?match=$matches[1]', 'top');
    }

    public function register_query_vars($vars) {
        $vars[] = 'match';
        return $vars;
    }

    public function match_template_include($template) {
        $match_id = get_query_var('match');
        if (!empty($match_id)) {
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/match-details.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    public static function activate() {
        add_rewrite_rule('^match/([0-9]+)/?$', 'index.php?match=$matches[1]', 'top');
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }



    private function get_api_key() {
        return $this->settings->get_api_key();
    }



    public function shortcode_handler($atts) {
        $atts = shortcode_atts(['game' => 'lol', 'limit' => 100, 'align' => 'center', 'type' => 'mixed'], $atts, 'pandascore_tracker');
        wp_enqueue_style('pandascore-tracker-style');
        wp_enqueue_script('pandascore-timezone-js');
        wp_enqueue_script('pandascore-league-filter-js');
        wp_enqueue_script('pandascore-date-filter-js');

        $this->live_match_ids = [];
        $html = '<div class="pandascore-tracker align-' . esc_attr($atts['align']) . '">';

        $html .= $this->renderer->render_date_filters();
        $html .= $this->renderer->render_league_filters();

        $html .= '<div class="pandascore-matches-wrapper">';
        if (in_array($atts['type'], ['live', 'mixed'])) {
            $live_content = $this->renderer->render_matches($this->api, $atts['game'], $atts['limit'], true, $this->live_match_ids);
            if (!empty($live_content)) {
                $html .= '<div class="pandascore-live-container">';
                $html .= $live_content;
                $html .= '</div>';
            }
        }
        if (in_array($atts['type'], ['upcoming', 'mixed'])) {
            $upcoming_content = $this->renderer->render_matches($this->api, $atts['game'], $atts['limit'], false, $this->live_match_ids);
            if (!empty($upcoming_content)) {
                $html .= '<div class="pandascore-upcoming-container">';
                $html .= $upcoming_content;
                $html .= '</div>';
            }
        }
        $html .= '</div>';

        // Enhanced live match detection and WebSocket setup
        if (in_array($atts['type'], ['live', 'mixed'])) {
            // Get live matches from tournaments (more comprehensive)
            $tournament_live_matches = $this->api->get_live_matches_from_tournaments($atts['game']);
            
            // Merge with matches from /running endpoint
            $all_live_match_ids = array_unique(array_merge(
                $this->live_match_ids,
                array_column($tournament_live_matches, 'match_id')
            ));

            if (!empty($all_live_match_ids)) {
                wp_enqueue_script('pandascore-live-tracker-js');
                $wsMatches = $this->api->get_ws_matches_payload($all_live_match_ids);
                
                wp_localize_script('pandascore-live-tracker-js', 'pandaScoreLiveTracker', [
                    'apiKey' => $this->get_api_key(),
                    'wsMatches' => $wsMatches,
                ]);

                error_log('[PandaScore] Initialized WebSocket tracking for ' . count($wsMatches) . ' matches');
            }
        }

        $html .= '</div>';
        return $html;
    }
}

register_activation_hook(__FILE__, ['PandaScore_Tracker_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['PandaScore_Tracker_Plugin', 'deactivate']);
new PandaScore_Tracker_Plugin();
