<?php

if (!defined('ABSPATH')) {
    exit;
}

class PandaScore_Shortcode {
    private $api;
    private $assets;
    private $renderer;
    private $settings;
    private $live_match_ids = [];

    public function __construct($api, $assets, $renderer, $settings) {
        $this->api = $api;
        $this->assets = $assets;
        $this->renderer = $renderer;
        $this->settings = $settings;
        add_shortcode('pandascore_tracker', [$this, 'handle']);
    }
    /**
     * Summary of handle ⚠️ NOTE the short codes are currently not working because we are still developing the app later we will customize the shortcode to filter the requests
     * @param mixed $atts
     * @return string
     */
    public function handle($atts) {
        // Trigger action to signal shortcode is being used (for fallback sync)
        do_action('pandascore_shortcode_loaded');
        
        $atts = shortcode_atts(['game' => 'lol', 'limit' => 100, 'align' => 'center', 'type' => 'mixed'], $atts, 'pandascore_tracker');
        $this->assets->enqueue_basic_assets();

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

        if (in_array($atts['type'], ['live', 'mixed'])) {
            $tournament_live_matches = $this->api->get_live_matches_from_tournaments($atts['game']);
            $all_live_match_ids = array_unique(array_merge(
                $this->live_match_ids,
                array_column($tournament_live_matches, 'match_id')
            ));

            if (!empty($all_live_match_ids)) {
                $wsMatches = $this->api->get_ws_matches_payload($all_live_match_ids);
                $this->assets->enqueue_live_tracker($this->settings->get_api_key(), $wsMatches);
                error_log('[PandaScore] Initialized WebSocket tracking for ' . count($wsMatches) . ' matches');
            }
        }

        $html .= '</div>';
        return $html;
    }
}