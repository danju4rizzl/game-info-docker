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

class PandaScore_Tracker_Plugin {
    private $live_match_ids = [];
    private $settings;
    private $api;

    public function __construct() {
        $this->settings = new PandaScore_Settings();
        $this->api = new PandaScore_API($this->settings);
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

    private function render_date_filters() {
        $dates = [];
        $now = current_time('timestamp'); // WP localized timestamp
        for ($i = 0; $i < 7; $i++) {
            $ts = $now + DAY_IN_SECONDS * $i;
            $dates[] = [
                'label' => $i === 0 ? __('Today', 'pandascore-tracker') : date_i18n('M j', $ts),
                'iso'   => date_i18n('Y-m-d', $ts),
            ];
        }

        $html = '<div class="pandascore-date-filters">';
        foreach ($dates as $d) {
            $html .= '<div class="pandascore-date-filter" data-date-iso="' . esc_attr($d['iso']) . '">';
            $html .= esc_html($d['label']);
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function render_league_filters() {
        $leagues = ['LCK', 'LPL', 'LEC', 'LTA'];

        $html = '<div class="pandascore-league-filters">';

        foreach ($leagues as $league_name) {
            $filename = str_replace(' ', '-', strtoupper($league_name)) . '-logo.png';
            $image_url = plugins_url('images/' . $filename, __FILE__);

            $html .= '<div class="pandascore-league-filter" data-league-name="' . esc_attr($league_name) . '" title="' . esc_attr($league_name) . '">';
            $html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($league_name) . '">';
            $html .= '</div>';
        }

        $other_leagues_filename = 'OTHERS-LEAGUES-logo.png';
        $other_leagues_image = plugins_url('images/' . $other_leagues_filename, __FILE__);
        $html .= '<div class="pandascore-league-filter" data-league-name="OTHER LEAGUES" title="OTHER LEAGUES">';
        $html .= '<img src="' . esc_url($other_leagues_image) . '" alt="OTHER LEAGUES">';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }



    private function get_team_logo_html($logo_url, $team_name, $acronym) {
        if ($logo_url) {
            return '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($team_name) . '" class="pandascore-team-logo">';
        }
        $fallback_letter = strtoupper(($acronym && $acronym !== 'TBD' && $acronym !== 'N/A') ? $acronym[0] : (($team_name && $team_name !== 'TBD' && $team_name !== 'N/A') ? $team_name[0] : '?'));
        return '<div class="pandascore-team-logo-placeholder" title="Unknown Team">' . esc_html($fallback_letter) . '</div>';
    }

    private function get_match_url($match) {
        $id = isset($match['id']) ? intval($match['id']) : 0;
        if (!$id) return '#';
        // Use query var URL to avoid dependency on rewrite flush; pretty URLs are also supported via rewrite rules
        return add_query_arg('match', $id, home_url('/'));
    }

    private function render_team($logo_url, $name, $acronym, $score = null, $opponent_id = null) {
        $html = '<div class="pandascore-team' . ($score !== null ? ' with-score' : '') . '">';
        $html .= '<div class="pandascore-team-info">';
        $html .= $this->get_team_logo_html($logo_url, $name, $acronym);
        $html .= '<span class="pandascore-team-name" title="' . esc_attr($name) . '">' . esc_html($acronym) . '</span>';
        $html .= '</div>';
        if ($score !== null) {
            $html .= '<div class="pandascore-score" data-opponent-id="' . esc_attr($opponent_id ?? '') . '">' . intval($score) . '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function render_match($match, $is_live = false) {
        $opponents = ['TBD', 'TBD'];
        $acronyms = ['TBD', 'TBD'];
        $logos = ['', ''];
        $scores = [0, 0];
        $opponent_ids = [null, null];

        if (isset($match['opponents']) && is_array($match['opponents'])) {
            foreach ($match['opponents'] as $i => $o) {
                if ($i < 2) {
                    $opponents[$i] = isset($o['opponent']['name']) ? esc_html($o['opponent']['name']) : 'TBD';
                    $acronyms[$i] = !empty($o['opponent']['acronym']) ? esc_html($o['opponent']['acronym']) : $opponents[$i];
                    $logos[$i] = $o['opponent']['image_url'] ?? '';
                    $opponent_ids[$i] = $o['opponent']['id'] ?? null;
                }
            }
        }

        if (isset($match['results']) && is_array($match['results'])) {
            foreach ($match['results'] as $i => $r) {
                if ($i < 2) $scores[$i] = intval($r['score'] ?? 0);
            }
        }

        $league_name = esc_html($match['league']['name'] ?? '');
        $league_logo = esc_url($match['league']['image_url'] ?? '');
        $league_id = esc_attr($match['league']['id'] ?? '');
        $scheduled_at = $match['scheduled_at'] ?? '';
        $is_upcoming = !$is_live && $scheduled_at;
        $match_url = $this->get_match_url($match);

        $html = '<div class="pandascore-match" data-league-id="' . $league_id . '" data-match-id="' . esc_attr($match['id'] ?? '') . ($is_upcoming ? '" data-scheduled-at="' . esc_attr($scheduled_at) : '') . '">';
        $html .= '<a class="pandascore-match-link" href="' . esc_url($match_url) . '">';
        $html .= '<div class="pandascore-league-container">';
        $html .= $league_logo ? '<div class="pandascore-league-logo"><img src="' . $league_logo . '" alt="' . $league_name . '" title="' . $league_name . '"></div>'
                             : '<div class="pandascore-league-placeholder" title="' . $league_name . '">' . ($league_name ? $league_name[0] : 'L') . '</div>';
        $html .= '</div>';

        $html .= '<div class="pandascore-match-content' . ($is_live ? ' live-layout' : '') . '">';
        $html .= '<div class="pandascore-teams-container">';
        $html .= $this->render_team($logos[0], $opponents[0], $acronyms[0], $is_live ? $scores[0] : null, $opponent_ids[0]);
        $html .= $this->render_team($logos[1], $opponents[1], $acronyms[1], $is_live ? $scores[1] : null, $opponent_ids[1]);
        $html .= '</div>';

        if ($is_upcoming) {
            $html .= '<div class="pandascore-time-container"><div class="pandascore-time-badge"><div class="pandascore-time">Loading...</div><div class="pandascore-time-day">Loading...</div></div></div>';
        }
        $html .= '</div>';
        $html .= '</a>';
        $html .= '</div>';
        return $html;
    }

    private function render_matches($game, $limit, $is_live) {
        $matches = $this->api->make_api_call($game, $limit, $is_live ? 'running' : 'upcoming');
        if (is_wp_error($matches)) {
            return '<div class="pandascore-error">Error: ' . esc_html($matches->get_error_message()) . '</div>';
        }
        if (empty($matches)) {
            return $is_live ? '' : '<div class="pandascore-no-matches">No upcoming matches found.</div>';
        }

        $html = '<div class="pandascore-section-header">' . ($is_live ? '<span class="pandascore-live-indicator"></span>LIVE' : 'UPCOMING') . '</div>';
        $html .= '<div class="pandascore-matches-container">';
        foreach ($matches as $match) {
            if ($is_live && isset($match['id'])) {
                $this->live_match_ids[] = $match['id'];
            }
            $html .= $this->render_match($match, $is_live);
        }
        $html .= '</div>';
        return $html;
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(['game' => 'lol', 'limit' => 100, 'align' => 'center', 'type' => 'mixed'], $atts, 'pandascore_tracker');
        wp_enqueue_style('pandascore-tracker-style');
        wp_enqueue_script('pandascore-timezone-js');
        wp_enqueue_script('pandascore-league-filter-js');
        wp_enqueue_script('pandascore-date-filter-js');

        $this->live_match_ids = [];
        $html = '<div class="pandascore-tracker align-' . esc_attr($atts['align']) . '">';

        $html .= $this->render_date_filters();
        $html .= $this->render_league_filters();

        $html .= '<div class="pandascore-matches-wrapper">';
        if (in_array($atts['type'], ['live', 'mixed'])) {
            $live_content = $this->render_matches($atts['game'], $atts['limit'], true);
            if (!empty($live_content)) {
                $html .= '<div class="pandascore-live-container">';
                $html .= $live_content;
                $html .= '</div>';
            }
        }
        if (in_array($atts['type'], ['upcoming', 'mixed'])) {
            $upcoming_content = $this->render_matches($atts['game'], $atts['limit'], false);
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
